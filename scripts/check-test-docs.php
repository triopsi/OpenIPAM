<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require dirname(__DIR__).'/vendor/autoload.php';

$directory = realpath($argv[1] ?? dirname(__DIR__).'/tests');

if ($directory === false || ! is_dir($directory)) {
    fwrite(STDERR, "The PHPUnit test directory does not exist.\n");
    exit(1);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
        require_once $file->getPathname();
    }
}

$checked = 0;
$errors = [];

foreach (get_declared_classes() as $className) {
    $class = new ReflectionClass($className);
    $path = $class->getFileName();

    if (! $class->isSubclassOf(TestCase::class) || ! $path || ! str_starts_with($path, $directory.DIRECTORY_SEPARATOR)) {
        continue;
    }

    foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $className) {
            continue;
        }

        if (! str_starts_with($method->getName(), 'test') && $method->getAttributes(Test::class) === []) {
            continue;
        }

        $checked++;
        $doc = $method->getDocComment() ?: '';
        $description = false;
        $expectation = false;

        foreach (preg_split('/\R/', $doc) as $line) {
            $line = trim(preg_replace('/^\s*\*\s?/', '', $line));

            if ($line === '' || in_array($line, ['/**', '/'], true) || str_starts_with($line, '@')) {
                continue;
            }

            if (str_starts_with($line, 'Expect:')) {
                $expectation = $expectation || trim(substr($line, strlen('Expect:'))) !== '';
            } else {
                $description = true;
            }
        }

        if (! $description || ! $expectation) {
            $errors[] = sprintf('%s:%d %s::%s needs a PHPDoc description and a nonempty Expect: line.', $path, $method->getStartLine(), $class->getShortName(), $method->getName());
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

if ($checked === 0) {
    fwrite(STDERR, "No PHPUnit test methods found.\n");
    exit(1);
}

printf("PHPDoc description and Expect: verified for %d PHPUnit test methods.\n", $checked);
