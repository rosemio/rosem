#!/usr/bin/env php
<?php

const APP_NAME = 'Rosem application console';
const PHP_VERSION_SUPPORTED = '7.4.0';

require_once __DIR__ . '/functions/throwError.php';

use function Rosem\Component\App\throwError;

// Check is PHP version is supported
if (version_compare(PHP_VERSION_SUPPORTED, PHP_VERSION, '>')) {
    throwError(
        sprintf(
            'This version of ' . APP_NAME . ' supports PHP ' . PHP_VERSION_SUPPORTED .
            ' and higher.' . PHP_EOL . 'You are using PHP %s (%s).',
            PHP_VERSION,
            PHP_BINARY
        )
    );
}

// Check if this file run as a CLI application
if (PHP_SAPI !== 'cli') {
    throwError(APP_NAME . ' must be run as a CLI application.');
}

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

// Require an autoload file
require_once __DIR__ . '/functions/findUp.php';

use function Rosem\Component\App\findUp;

$autoloadFile = findUp(__DIR__, 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php', 6);

if ($autoloadFile === null) {
    throwError(
        'You need to set up the project dependencies using Composer under application root directory:' .
        PHP_EOL . PHP_EOL . '    composer install' . PHP_EOL . PHP_EOL .
        'You can learn all about Composer on https://getcomposer.org/.'
    );
}

require_once $autoloadFile;

unset($autoloadFile);

use Rosem\Component\Hash\Console\HashGenerateCommand;
use Rosem\Component\Encryption\Console\KeyGenerateCommand;
use Symfony\Component\Console\Application;

$application = new Application();

if (class_exists(HashGenerateCommand::class)) {
    $application->add(new HashGenerateCommand());
}

if (class_exists(KeyGenerateCommand::class)) {
    $application->add(new KeyGenerateCommand());
}

$application->run();
