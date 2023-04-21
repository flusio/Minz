<?php

namespace Minz\Job;

use Minz\Job;
use Minz\Request;
use Minz\Response;

/**
 * The Job\Controller allows to manage the jobs with CLI commands.
 *
 * The controller provides 6 actions:
 *
 * - watch: to start a Jobs Worker to execute jobs in a loop
 * - run: to execute a specific job
 * - index: to list the jobs
 * - show: to display the information about a job
 * - unfail: to discard the error of a job
 * - unlock: to unlock a locked job
 *
 * To connect this controller to your application, start by creating a
 * controller that inherit from it:
 *
 *     namespace App;
 *
 *     class Jobs extends \Minz\Job\Controller
 *     {
 *     }
 *
 * Then, add the routes to your Router, for instance:
 *
 *     $router = new \Minz\Router();
 *     $router->addRoute('CLI', '/jobs', 'Jobs#index');
 *     $router->addRoute('CLI', '/jobs/watch', 'Jobs#watch');
 *     $router->addRoute('CLI', '/jobs/show', 'Jobs#show');
 *     // ...
 *
 * You can easily create new actions in your own Controller if you need to.
 *
 * Note that your ./cli script must be able to handle Responses Generators as
 * the watch() method yields its responses. This is the case of the
 * \Minz\Response::sendToCli() method:
 *
 *     $request = \Minz\Request::initFromCli($argv);
 *
 *     $application = new \App\Application();
 *     $response = $application->run($request);
 *
 *     \Minz\Response::sendToCli($response);
 *
 * @phpstan-import-type ResponseGenerator from Response
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Controller
{
    public bool $is_watching = true;

    /**
     * Start a job worker which call `run()` in a loop. This action should be
     * called via a systemd service, or as any other kind of "init" service.
     *
     * Responses are yield during the lifetime of the action.
     *
     * @request_param string queue
     *     Selects jobs in the given queue (default: all). Numbers at the end
     *     of the queue are ignored, so it allows to identify worker with, e.g.
     *     fetchers1, fetchers2, etc.
     *
     * @request_param int stop-after
     *     Set a maximum number of jobs to run, default is infinite (it can be
     *     stopped by sending a SIGINT or SIGTERM signal to the program).
     *
     * @request_param int sleep-duration
     *     Set the sleep duration between two cycles, default is 3 (seconds).
     *
     * @response 500 If an error happens when executing a job
     * @response 200
     *
     * @return ResponseGenerator
     */
    public function watch(Request $request): \Generator
    {
        \pcntl_async_signals(true);
        \pcntl_signal(SIGTERM, [$this, 'stopWatch']);
        \pcntl_signal(SIGINT, [$this, 'stopWatch']);

        $this->is_watching = true;

        /** @var int */
        $stop_after = $request->paramInteger('stop-after', 0);

        /** @var int */
        $sleep_duration = $request->paramInteger('sleep-duration', 3);

        /** @var string */
        $queue = $request->param('queue', 'all');
        $queue = rtrim($queue, '0..9');

        yield Response::text(200, "[Job worker ({$queue}) started]");

        $count_jobs = 0;

        while ($this->is_watching) {
            $job_id = Job::findNextJobId($queue);

            if ($job_id !== null) {
                $request_run = new Request('CLI', '/jobs/run', [
                    'id' => $job_id,
                ]);

                yield $this->run($request_run);

                // Close the connection to the database to avoid performance
                // issues.
                $database = \Minz\Database::get();
                $database->close();

                $count_jobs += 1;
            }

            if ($stop_after > 0 && $count_jobs >= $stop_after) {
                $this->is_watching = false;
            }

            if ($this->is_watching) {
                \Minz\Time::sleep($sleep_duration);
            }
        }

        yield Response::text(200, "[Job worker ({$queue}) stopped]");
    }

    /**
     * Handler to catch signals and stop the worker.
     */
    public function stopWatch(): void
    {
        $this->is_watching = false;
    }

    /**
     * Run the given job (even if it's not its time yet!)
     *
     * @request_param int id
     *
     * @response 404 If the job does not exist
     * @response 500 If an error happens when executing a job
     * @response 200
     */
    public function run(Request $request): Response
    {
        /** @var int */
        $job_id = $request->paramInteger('id', 0);

        // Load is similar to find, except that it returns a Job as its final
        // class. For instance, if $job_id corresponds to a Job with the name
        // `\DummyJob`, it will return a DummyJob instead of a Job.
        $job = Job::load($job_id);

        if (!$job) {
            return Response::text(404, "Job {$job_id} does not exist.");
        }

        if (!is_callable([$job, 'perform'])) {
            \Minz\Log::error("{$job->name} class does not declare any perform() method.");
            $job->remove();
            return Response::internalServerError();
        }

        if (!$job->lock()) {
            return Response::internalServerError();
        }

        $time_start = microtime(true);

        $job->number_attempts += 1;
        $job->save();

        try {
            $job->perform(...$job->args);

            if ($job->frequency) {
                $job->reschedule();
                $job->unlock();
            } else {
                $job->remove();
            }

            $error = false;
        } catch (\Exception $exception) {
            \Minz\Log::error($exception->getMessage());

            $job->fail((string)$exception);
            $job->unlock();

            $error = true;
        }

        $time_end = microtime(true);
        $time = number_format($time_end - $time_start, 3);

        if ($error) {
            return Response::text(500, "job#{$job->id} ({$job->name}): failed (in {$time} seconds)");
        } else {
            return Response::text(200, "job#{$job->id} ({$job->name}): done (in {$time} seconds)");
        }
    }

    /**
     * List all the current jobs
     *
     * @response 200
     */
    public function index(Request $request): Response
    {
        $jobs = Job::listAll();
        usort($jobs, function ($job_1, $job_2) {
            return $job_1->id - $job_2->id;
        });

        $date_format = \Minz\Database\Column::DATETIME_FORMAT;

        $result = [];
        foreach ($jobs as $job) {
            $job_as_text = "job#{$job->id} {$job->name}";
            $perform_at = $job->perform_at->format($date_format);
            if ($job->frequency) {
                $job_as_text .= " scheduled each {$job->frequency}, next at {$perform_at}";
            } else {
                $job_as_text .= " at {$perform_at}, {$job->number_attempts} attempts";
            }

            if ($job->locked_at) {
                $job_as_text .= ' (locked)';
            }

            if ($job->failed_at) {
                $job_as_text .= ' (failed)';
            }

            $result[] = $job_as_text;
        }

        return Response::text(200, implode("\n", $result));
    }

    /**
     * Display the information about a job.
     *
     * @request_param int id
     *
     * @response 404 If the job doesn't exist
     * @response 200
     */
    public function show(Request $request): Response
    {
        /** @var int */
        $job_id = $request->paramInteger('id', 0);
        $job = Job::find($job_id);

        if (!$job) {
            return Response::text(404, "Job {$job_id} does not exist.");
        }

        $result = '';
        $result .= "id: {$job->id}";
        $result .= "\nname: {$job->name}";

        $date_format = \Minz\Database\Column::DATETIME_FORMAT;

        if ($job->args) {
            $args = array_map(function ($arg) {
                return var_export($arg, true);
            }, $job->args);

            $args = implode(', ', $args);

            $result .= "\nargs: {$args}";
        } else {
            $result .= "\nargs: none";
        }

        $result .= "\nperform: {$job->perform_at->format($date_format)}";
        $result .= "\nattempts: {$job->number_attempts}";
        $result .= "\nqueue: {$job->queue}";

        if ($job->frequency) {
            $result .= "\nrepeat: {$job->frequency}";
        } else {
            $result .= "\nrepeat: once";
        }

        $result .= "\ncreated: {$job->created_at->format($date_format)}";
        $result .= "\nupdated: {$job->updated_at->format($date_format)}";

        if ($job->locked_at) {
            $result .= "\nlocked: {$job->locked_at->format($date_format)}";
        }

        if ($job->failed_at) {
            $result .= "\nfailed: {$job->failed_at->format($date_format)}";
            $result .= "\n{$job->last_error}";
        } else {
            $result .= "\nfailed: never";
        }

        return Response::text(200, $result);
    }

    /**
     * Discard the error of a job.
     *
     * @request_param int id
     *
     * @response 404 If the job doesn't exist
     * @response 200
     */
    public function unfail(Request $request): Response
    {
        /** @var int */
        $job_id = $request->paramInteger('id', 0);
        $job = Job::find($job_id);

        if (!$job) {
            return Response::text(404, "Job {$job_id} does not exist.");
        }

        if (!$job->failed_at) {
            return Response::text(200, "Job {$job->id} has not failed.");
        }

        $error = $job->last_error;
        $job->last_error = '';
        $job->failed_at = null;
        $job->save();

        return Response::text(200, "Job {$job->id} is no longer failing, was:\n{$error}");
    }

    /**
     * Unlock a job.
     *
     * @request_param int id
     *
     * @response 404 If the job doesn't exist
     * @response 200
     */
    public function unlock(Request $request): Response
    {
        /** @var int */
        $job_id = $request->paramInteger('id', 0);
        $job = Job::find($job_id);

        if (!$job) {
            return Response::text(404, "Job {$job_id} does not exist.");
        }

        if (!$job->isLocked()) {
            return Response::text(200, "Job {$job->id} was not locked.");
        }

        $job->unlock();

        return Response::text(200, "Job {$job->id} lock has been released.");
    }
}
