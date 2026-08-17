<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Migrator
{
    public function __construct(
        private PDO $connection,
        private string $migrationPath
    ) {
    }

    public function run(): void
    {
        $this->createMigrationsTable();

        $executed = $this->getExecutedMigrations();

        $files = glob($this->migrationPath . '/*.sql');

        if ($files === false) {
            throw new \RuntimeException('Unable to read migration directory.');
        }

        sort($files);

        foreach ($files as $file) {
            $migration = basename($file);

            if (isset($executed[$migration])) {
                continue;
            }

            echo "Running migration: {$migration}" . PHP_EOL;

            $sql = file_get_contents($file);

            if ($sql === false) {
                throw new \RuntimeException(
                    "Unable to read migration: {$migration}"
                );
            }

            $this->connection->beginTransaction();

            try {
                $this->connection->exec($sql);

                $statement = $this->connection->prepare(
                    'INSERT INTO migrations (migration) VALUES (:migration)'
                );

                $statement->execute([
                    'migration' => $migration,
                ]);

                $this->connection->commit();

                echo "Completed: {$migration}" . PHP_EOL;
            } catch (\Throwable $exception) {
                $this->connection->rollBack();

                throw $exception;
            }
        }

        echo "Migrations complete." . PHP_EOL;
    }

    private function createMigrationsTable(): void
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function getExecutedMigrations(): array
    {
        $statement = $this->connection->query(
            'SELECT migration FROM migrations'
        );

        $migrations = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_fill_keys($migrations, true);
    }
}
