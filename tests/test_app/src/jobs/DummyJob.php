<?php

namespace AppTest\jobs;

use Minz\Job;

class DummyJob extends Job
{
    public function perform(bool $should_fail = false): void
    {
        if ($should_fail) {
            throw new \Exception('oops');
        }
    }
}
