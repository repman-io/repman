<?php

declare(strict_types=1);

use Buddy\Repman\Tests\TestStorage;

require dirname(__DIR__).'/config/bootstrap.php';

// before anything runs, so a previous run that died mid-test cannot decide what this
// one starts from
TestStorage::reset();
