<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('docker')
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_to_comment' => false,
        'global_namespace_import' => false,
        'nullable_type_declaration' => true
    ])
    ->setFinder($finder)
;
