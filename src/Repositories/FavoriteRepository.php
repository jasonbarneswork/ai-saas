<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class FavoriteRepository
{
    public function __construct(
        private Database $database
    ) {
    }

    public function create(
        int $userId,
        int $listingId
    ): bool {
        $sql = '
            INSERT INTO favorites (
                user_id,
                listing_id
            )
            VALUES (
                :user_id,
                :listing_id
            )
            ON CONFLICT (user_id, listing_id)
            DO NOTHING
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'user_id'    => $userId,
            'listing_id' => $listingId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(
        int $userId,
        int $listingId
    ): bool {
        $sql = '
            DELETE FROM favorites
            WHERE user_id = :user_id
              AND listing_id = :listing_id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'user_id'    => $userId,
            'listing_id' => $listingId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function exists(
        int $userId,
        int $listingId
    ): bool {
        $sql = '
            SELECT 1
            FROM favorites
            WHERE user_id = :user_id
              AND listing_id = :listing_id
            LIMIT 1
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'user_id'    => $userId,
            'listing_id' => $listingId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findAllByUserId(
        int $userId
    ): array {
        $sql = '
            SELECT
                l.id,
                l.user_id,
                l.category_id,
                l.title,
                l.description,
                l.price,
                l.currency,
                l.condition,
                l.status,
                l.published_at,
                l.created_at,
                l.updated_at,
                f.created_at AS favorited_at
            FROM favorites f
            INNER JOIN listings l
                ON l.id = f.listing_id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'user_id' => $userId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}