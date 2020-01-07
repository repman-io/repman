<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP71Migration' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'ordered_imports' => [
            'importsOrder' => null,
            'sortAlgorithm' => 'alpha',
        ],
        'phpdoc_order' => true,
        'phpdoc_align' => true,
        'phpdoc_no_access' => true,
        'phpdoc_separation' => true,
        'pre_increment' => true,
        'single_quote' => true,
        'trim_array_spaces' => true,
        'single_blank_line_before_namespace' => true,
        'yoda_style' => null,
        // risky -->
        'strict_param' => true,
    ])
    ->setFinder($finder)
;
