<?php

$root = dirname(__DIR__);
$paths = array(
    $root . DIRECTORY_SEPARATOR . 'index.php',
    $root . DIRECTORY_SEPARATOR . 'application'
);

$files = array();
foreach ($paths as $path) {
    if (is_file($path) && substr($path, -4) === '.php') {
        $files[] = $path;
        continue;
    }

    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);

$failed = array();
foreach ($files as $file) {
    $command = PHP_BINARY . ' -l ' . escapeshellarg($file);
    exec($command, $output, $status);
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
    $output = array();

    if ($status !== 0) {
        $failed[] = $file;
    }
}

if ($failed) {
    fwrite(STDERR, PHP_EOL . 'Lint failed for ' . count($failed) . ' file(s).' . PHP_EOL);
    exit(1);
}

echo PHP_EOL . 'Lint passed for ' . count($files) . ' PHP file(s).' . PHP_EOL;
