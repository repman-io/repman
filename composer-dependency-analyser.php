<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

// Bundles, providers and adapters below are used through configuration (config/), which the
// analyser does not read.
return (new Configuration())
    ->addPathToScan(__DIR__.'/bin', isDev: false)
    ->ignoreErrorsOnPackages([
        'doctrine/doctrine-migrations-bundle',
        'league/flysystem-aws-s3-v3',
        'league/flysystem-bundle',
        'league/oauth2-github',
        'nelmio/cors-bundle',
        'nyholm/psr7',
        'omines/oauth2-gitlab',
        'ramsey/uuid-doctrine',
        'sentry/sentry-symfony',
        'stevenmaguire/oauth2-bitbucket',
        'symfony/amazon-mailer',
        'symfony/asset',
        'symfony/doctrine-messenger',
        'symfony/dotenv',
        'symfony/flex',
        'symfony/monolog-bundle',
        'symfony/security-bundle',
        'symfony/twig-bundle',
        'symfony/yaml',
    ], [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnExtensions(['ext-ctype', 'ext-curl', 'ext-iconv', 'ext-pdo_pgsql'], [ErrorType::UNUSED_DEPENDENCY])
    // fixtures are only loaded in dev and test
    ->ignoreErrorsOnPath(__DIR__.'/src/DataFixtures', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
