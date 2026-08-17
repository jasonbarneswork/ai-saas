<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Database\Migrator;

$database = new Database();

$migrator = new Migrator(
    $database->getConnection(),
    __DIR__ . '/../database/migrations'
);

$migrator->run();
