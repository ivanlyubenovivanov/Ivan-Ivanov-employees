<?php


$directory = __DIR__ . '/src';
$handle = opendir($directory);

if ($handle === false) {
    throw new RuntimeException('Cannot open source directory.');
}

try {
    while (($file = readdir($handle)) !== false) {
        $path = $directory . '/' . $file;

        if (is_file($path) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            require_once $path;
        }
    }
} finally {
    closedir($handle);
}