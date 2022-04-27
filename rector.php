<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/bin',
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::CODE_QUALITY
    ]);
    $rectorConfig->parallel();
    $rectorConfig->importNames();
    $rectorConfig->phpVersion(PhpVersion::PHP_74);
    $rectorConfig->services()->set(NoUnusedImportsFixer::class);

    // this will not import root namespace classes, like \DateTime or \Exception
    $rectorConfig->parameters()->set(Option::IMPORT_SHORT_CLASSES, false);
};
