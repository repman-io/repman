<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/tests/Resources',
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true, sensiolabs: true)
    ->withConfiguredRule(AnnotationToAttributeRector::class, [
        new AnnotationToAttribute('OpenApi\Annotations\Tag', 'OpenApi\Attributes\Tag'),
        new AnnotationToAttribute('OpenApi\Annotations\Response', 'OpenApi\Attributes\Response'),
        new AnnotationToAttribute('OpenApi\Annotations\RequestBody', 'OpenApi\Attributes\RequestBody'),
        new AnnotationToAttribute('OpenApi\Annotations\JsonContent', 'OpenApi\Attributes\JsonContent'),
        new AnnotationToAttribute('Nelmio\ApiDocBundle\Annotation\Model', 'Nelmio\ApiDocBundle\Attribute\Model'),
    ])
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true, twig: true);
