<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] ?? 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? true;
$_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] ?? 'test-secret';
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] ?? 'sqlite:///' . dirname(__DIR__) . '/var/jobboard_test.db';

$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);
$application->run(new ArrayInput([
    'command' => 'doctrine:schema:update',
    '--force' => true,
    '--quiet' => true,
]));

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
