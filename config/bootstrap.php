<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// loads .env, .env.local, .env.$APP_ENV and .env.$APP_ENV.local, or the cached .env.local.php
// written by "composer dump-env prod", and normalizes APP_ENV / APP_DEBUG
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
