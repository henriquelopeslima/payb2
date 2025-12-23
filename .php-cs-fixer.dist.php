<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in(__DIR__)
    ->exclude(['logs', 'var', 'vendor', 'node_modules']);

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'yoda_style' => true,
        'single_line_empty_body' => true,
        'concat_space' => ['spacing' => 'none'],
        'global_namespace_import' => true,
    ])
    ->setFinder($finder);
