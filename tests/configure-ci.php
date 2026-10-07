<?php

// CI uses the sibling packages' working branches through Composer path repositories.
$root = dirname(__DIR__);
$composer = json_decode(file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$versions = [];
foreach (glob($root.'/.ci-packages/*/composer.json') as $manifest) {
    $package = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
    $versions[$package['name']] = '2.5.1';
}
$composer['repositories'] = [['type' => 'path', 'url' => '.ci-packages/*', 'options' => ['symlink' => true, 'versions' => $versions]]];
$composer['require']['laravel/framework'] = getenv('LARAVEL_CONSTRAINT') ?: '^11.0';
$composer['require-dev']['orchestra/testbench'] = getenv('TESTBENCH_CONSTRAINT') ?: '^9.0';
file_put_contents($root.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
