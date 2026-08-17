<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $host     = getenv('DATABASE_HOST');
        $port     = getenv('DATABASE_PORT');
        $database = getenv('DATABASE_NAME');
        $username = getenv('DATABASE_USER');
        $password = getenv('DATABASE_PASSWORD');

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new PDOException(
                'Database connection failed: ' . $exception->getMessage(),
                (int) $exception->getCode()
            );
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
