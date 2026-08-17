<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class CategoryRepository
{
    public function __construct(
        private Database $database
    ) {
    }

    public function findAll(): array
    {
        $sql = '
            SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                created_at,
                updated_at
            FROM categories
            ORDER BY name ASC
        ';

        $statement = $this->database
            ->getConnection()
            ->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                created_at,
                updated_at
            FROM categories
            WHERE id = :id
            LIMIT 1
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $category = $statement->fetch(PDO::FETCH_ASSOC);

        return $category !== false ? $category : null;
    }
}
