<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/recipe')
    ->in(__DIR__ . '/contrib')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/vendor'); // Also fix vendor files as we have them under git control.

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        'nullable_type_declaration_for_default_null_value' => true,
    ])
    ->setFinder($finder);
