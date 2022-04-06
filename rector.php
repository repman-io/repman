<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    // here we can define, what sets of rules will be applied
    // tip: use "SetList" class to autocomplete sets
    $containerConfigurator->import(LevelSetList::UP_TO_PHP_74);

    // register single rule
    $services = $containerConfigurator->services();
    $services->set(NoUnusedImportsFixer::class);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::AUTO_IMPORT_NAMES, true)
        ->set(Option::PARALLEL, true)
        ->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_74)
        ->set(Option::PATHS, [
            __DIR__ . '/bin',
            __DIR__ . '/src',
            __DIR__ . '/tests'
        ]);
};
