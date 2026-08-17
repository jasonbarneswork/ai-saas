<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class ListingRepository
{
    public function __construct(
        private Database $database
    ) {
    }

    public function create(array $data): array
    {
        $sql = '
            INSERT INTO listings (
                user_id,
                category_id,
                title,
                description,
                price,
                currency,
                condition
            )
            VALUES (
                :user_id,
                :category_id,
                :title,
                :description,
                :price,
                :currency,
                :condition
            )
            RETURNING
                id,
                user_id,
                category_id,
                title,
                description,
                price,
                currency,
                condition,
                created_at,
                updated_at
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'USD',
            'condition' => $data['condition'] ?? null,
        ]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll(array $filters = []): array
    {
        $conditions = [];
        $parameters = [];

        if (!empty($filters['category_id'])) {
            $conditions[] = 'l.category_id = :category_id';
            $parameters['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(
                l.title ILIKE :search
                OR l.description ILIKE :search
            )';

            $parameters['search'] = '%' . $filters['search'] . '%';
        }

        if (
            isset($filters['min_price']) &&
            $filters['min_price'] !== ''
        ) {
            $conditions[] = 'l.price >= :min_price';
            $parameters['min_price'] = $filters['min_price'];
        }

        if (
            isset($filters['max_price']) &&
            $filters['max_price'] !== ''
        ) {
            $conditions[] = 'l.price <= :max_price';
            $parameters['max_price'] = $filters['max_price'];
        }

        $where = '';

        if ($conditions) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $page = max(
            1,
            (int) ($filters['page'] ?? 1)
        );

        $limit = min(
            100,
            max(
                1,
                (int) ($filters['limit'] ?? 20)
            )
        );

        $offset = ($page - 1) * $limit;

        /*
        * Count total matching listings.
        */
        $countSql = "
            SELECT COUNT(*)
            FROM listings l
            {$where}
        ";

        $countStatement = $this->database
            ->getConnection()
            ->prepare($countSql);

        foreach ($parameters as $name => $value) {
            $countStatement->bindValue(
                ':' . $name,
                $value
            );
        }

        $countStatement->execute();

        $total = (int) $countStatement->fetchColumn();

        /*
        * Fetch the requested page.
        */
        $sql = "
            SELECT
                l.id,
                l.title,
                l.description,
                l.price,
                l.currency,
                l.condition,
                l.created_at,
                l.updated_at,

                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,

                u.id AS seller_id,
                u.first_name AS seller_first_name,
                u.last_name AS seller_last_name

            FROM listings l

            INNER JOIN categories c
                ON c.id = l.category_id

            INNER JOIN users u
                ON u.id = l.user_id

            {$where}

            ORDER BY l.created_at DESC

            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value
            );
        }

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        $listings = $statement->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items'       => $listings,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $total > 0
                ? (int) ceil($total / $limit)
                : 0,
        ];
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT
                l.id,
                l.title,
                l.description,
                l.price,
                l.currency,
                l.condition,
                l.created_at,
                l.updated_at,

                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,

                u.id AS seller_id,
                u.first_name AS seller_first_name,
                u.last_name AS seller_last_name

            FROM listings l

            INNER JOIN categories c
                ON c.id = l.category_id

            INNER JOIN users u
                ON u.id = l.user_id

            WHERE l.id = :id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id' => $id
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function update(
        int $id,
        int $userId,
        array $data
    ): ?array {
        $sql = '
            UPDATE listings
            SET
                category_id = :category_id,
                title = :title,
                description = :description,
                price = :price,
                currency = :currency,
                condition = :condition,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
            AND user_id = :user_id
            RETURNING
                id,
                user_id,
                category_id,
                title,
                description,
                price,
                currency,
                condition,
                created_at,
                updated_at
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id'          => $id,
            'user_id'     => $userId,
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'currency'    => $data['currency'] ?? 'USD',
            'condition'   => $data['condition'] ?? null,
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }


    public function delete(
        int $listingId,
        int $userId
    ): bool {
        $sql = '
            DELETE FROM listings
            WHERE id = :id
            AND user_id = :user_id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id'      => $listingId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function findByIdForUser(
        int $listingId,
        int $userId
    ): ?array {
        $sql = '
            SELECT
                id,
                user_id,
                category_id,
                title,
                description,
                price,
                currency,
                condition,
                created_at,
                updated_at
            FROM listings
            WHERE id = :listing_id
            AND user_id = :user_id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'listing_id' => $listingId,
            'user_id'    => $userId,
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
