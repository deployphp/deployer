<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/recipe')
    ->in(__DIR__ . '/contrib')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,

        // Due to historical reasons we have to keep this.
        // Docs parser expects comment right after php tag.
        'blank_line_after_opening_tag' => false,

        // For PHP 7.4 compatibility.
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'array_destructuring', 'arrays']
        ],
    ])
    ->setFinder($finder);
