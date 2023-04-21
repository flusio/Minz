<?php

namespace Minz;

/**
 * The Job class is a Recordable model that allows to manage asynchronous jobs.
 *
 * Jobs are handled by one or several Jobs Watchers. A Job Watcher is simply a
 * CLI controller action which waits for new jobs to be executed.
 *
 * @see Job\Controller::watch
 *
 * Your jobs must inherit from the Job class and implements the perform() method:
 *
 *     use Minz\Job;
 *
 *     class MyJob extends Job
 *     {
 *         public function perform(string $param)
 *         {
 *             // do something
 *         }
 *     }
 *
 * Then, you can either execute the job immediately, or delay it for later:
 *
 *     $my_job = new MyJob();
 *
 *     // execute the job immediately
 *     $my_job->perform('foo');
 *
 *     // the Jobs Watcher will execute it as soon as possible
 *     $my_job->performAsap('foo');
 *
 *     // the Jobs Watcher will execute it in 1 hour
 *     $perform_at = \Minz\Time::fromNow(1, 'hour');
 *     $my_job->performLater($perform_at, 'foo');
 *
 * You can create a job that will repeat over time (similarly to a cron job).
 * For that, you need to specify a frequency:
 *
 *     class MyJob extends Job
 *     {
 *         public function __construct()
 *         {
 *             parent::__construct();
 *             $this->frequency = '+1 hour';
 *         }
 *     }
 *
 * The frequency accepts any DateTime modifier, but you must make sure that it
 * is a "positive" modifier (i.e. applying the modifier to a datetime makes it
 * going in the future).
 *
 * @see https://www.php.net/manual/datetime.formats.relative.php
 *
 * A job can be put into a specific queue (default queue is "default"):
 *
 *     class MyJob extends Job
 *     {
 *         public function __construct()
 *         {
 *             parent::__construct();
 *             $this->queue = 'my queue';
 *         }
 *     }
 *
 * Queues are useful to dispatch the jobs across different Jobs Watchers.
 *
 * When a job fails, the last error is saved in the last_error column.
 *
 * Jobs are stored in the database. You’ll need to create its table.
 * For SQLite:
 *
 * CREATE TABLE jobs (
 *     id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 *     created_at TEXT NOT NULL,
 *     updated_at TEXT NOT NULL,
 *     perform_at TEXT NOT NULL,
 *     name TEXT NOT NULL DEFAULT '',
 *     args TEXT NOT NULL DEFAULT '{}',
 *     frequency TEXT NOT NULL DEFAULT '',
 *     queue TEXT NOT NULL DEFAULT 'default',
 *     locked_at TEXT,
 *     number_attempts BIGINT NOT NULL DEFAULT 0,
 *     last_error TEXT NOT NULL DEFAULT '',
 *     failed_at TEXT
 * );
 *
 * For PostgreSQL:
 *
 * CREATE TABLE jobs (
 *     id SERIAL PRIMARY KEY,
 *     created_at TIMESTAMPTZ NOT NULL,
 *     updated_at TIMESTAMPTZ NOT NULL,
 *     perform_at TIMESTAMPTZ NOT NULL,
 *     name TEXT NOT NULL DEFAULT '',
 *     args JSON NOT NULL DEFAULT '{}',
 *     frequency TEXT NOT NULL DEFAULT '',
 *     queue TEXT NOT NULL DEFAULT 'default',
 *     locked_at TIMESTAMPTZ,
 *     number_attempts BIGINT NOT NULL DEFAULT 0,
 *     last_error TEXT NOT NULL DEFAULT '',
 *     failed_at TIMESTAMPTZ
 * );
 *
 * @phpstan-type JobArg string|int|bool|null
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'jobs')]
class Job
{
    use Database\Recordable;
    use Database\Lockable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $name;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public \DateTimeImmutable $updated_at;

    /** @var JobArg[] */
    #[Database\Column]
    public array $args;

    #[Database\Column]
    public \DateTimeImmutable $perform_at;

    #[Database\Column]
    public string $frequency = '';

    #[Database\Column]
    public string $queue = 'default';

    #[Database\Column]
    public ?\DateTimeImmutable $locked_at = null;

    #[Database\Column]
    public int $number_attempts = 0;

    #[Database\Column]
    public string $last_error = '';

    #[Database\Column]
    public ?\DateTimeImmutable $failed_at = null;

    public function __construct()
    {
        $this->name = static::class;
    }

    /**
     * Store the job to be executed by the jobs watcher as soon as possible.
     *
     * @param JobArg ...$args
     */
    public function performAsap(mixed ...$args): void
    {
        $this->performLater(\Minz\Time::now(), ...$args);
    }

    /**
     * Store the job to be executed by the jobs watcher at the given time.
     *
     * @param JobArg ...$args
     */
    public function performLater(\DateTimeImmutable $perform_at, mixed ...$args): void
    {
        $this->perform_at = $perform_at;
        $this->args = $args;
        $this->save();
    }

    /**
     * Return the next job id to be executed from the given queue.
     *
     * If $queue equals to "all", it will return the next job from any queue.
     */
    public static function findNextJobId(string $queue): ?int
    {
        $parameters = [
            ':perform_at' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
            ':lock_timeout' => \Minz\Time::ago(1, 'hour')->format(Database\Column::DATETIME_FORMAT),
        ];

        $queue_placeholder = '';
        if ($queue !== 'all') {
            $queue_placeholder = 'AND queue = :queue';
            $parameters[':queue'] = $queue;
        }

        $sql = <<<SQL
            SELECT id FROM jobs

            WHERE (locked_at IS NULL OR locked_at <= :lock_timeout)
            AND perform_at <= :perform_at
            AND (number_attempts <= 25 OR frequency != '')
            {$queue_placeholder}

            ORDER BY perform_at ASC
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetchColumn();
        if ($result === false) {
            return null;
        }

        return intval($result);
    }

    /**
     * Find a Job and return it by instantiating it with its "name" class.
     *
     * The "name" class must exist and be a subclass of Job, or the method will
     * return null.
     */
    public static function load(int $job_id): ?self
    {
        $sql = <<<SQL
            SELECT * FROM jobs
            WHERE id = :id
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([':id' => $job_id]);

        $result = $statement->fetch();
        if (!is_array($result)) {
            return null;
        }

        $class_name = $result['name'] ?? '';
        if (!is_subclass_of($class_name, Job::class)) {
            return null;
        }

        return $class_name::fromDatabaseRow($result);
    }

    /**
     * Reschedule the current job for later based on its frequency.
     *
     * @throws \DomainException
     *     If the Job frequency goes backward.
     */
    public function reschedule(): void
    {
        if (!$this->frequency) {
            return;
        }

        $this->perform_at = $this->nextPerformAt();
        $this->save();
    }

    /**
     * Return the next perform_at of the current job.
     *
     * It takes the current perform_at and it modifies it with its "frequency"
     * until the date is in the future.
     *
     * @throws \LogicException
     *     If the Job has no frequency.
     * @throws \DomainException
     *     If the Job frequency goes backward.
     */
    private function nextPerformAt(): \DateTimeImmutable
    {
        if (!$this->frequency) {
            $class_name = static::class;
            throw new \LogicException("{$class_name} cannot be reschedule as it has no frequency");
        }

        $timezone = new \DateTimeZone(date_default_timezone_get());
        $date = $this->perform_at->setTimezone($timezone);

        while ($date <= \Minz\Time::now()) {
            $new_date = $date->modify($this->frequency);

            if ($new_date < $date) {
                $class_name = static::class;
                throw new \DomainException("{$class_name} has a frequency going backward");
            }

            $date = $new_date;
        }

        return $date;
    }

    /**
     * Mark the current job as failing and reschedule it for later.
     */
    public function fail(string $error): void
    {
        if ($this->frequency) {
            $next_perform_at = $this->nextPerformAt();
        } else {
            $number_seconds = 5 + pow($this->number_attempts, 4);
            $next_perform_at = \Minz\Time::fromNow($number_seconds, 'seconds');
        }

        $this->perform_at = $next_perform_at;
        $this->failed_at = \Minz\Time::now();
        $this->last_error = $error;
        $this->save();
    }
}
