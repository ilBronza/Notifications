<?php

$root = dirname(__DIR__);
foreach (['src', 'tests', 'database', 'config', 'routes', 'resources/lang'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1', $output, $code);
            if ($code !== 0) { fwrite(STDERR, implode("\n", $output)."\n"); exit($code); }
            $output = [];
        }
    }
}
echo "PHP syntax OK\n";
