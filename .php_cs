<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__);

$fixers = [
    'short_array_syntax',
];

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers($fixers)
    ->finder($finder);
