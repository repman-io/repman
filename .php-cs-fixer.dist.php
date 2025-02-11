<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/bin')
    ->in(__DIR__.'/config')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/public')
    ->in(__DIR__.'/tests')
;

$config = new PhpCsFixer\Config();
return $config->setRiskyAllowed(true)
    ->setRules([
        '@PHP81Migration' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_between_import_groups' => false,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => false,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_order' => true,
        'phpdoc_align' => true,
        'phpdoc_no_access' => true,
        'phpdoc_separation' => true,
        'increment_style' => true,
        'single_quote' => true,
        'trim_array_spaces' => true,
        'yoda_style' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        // risky -->
        'strict_param' => true,
    ])
    ->setFinder($finder)
;
