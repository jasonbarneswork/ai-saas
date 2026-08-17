<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class ListingImageRepository
{
    public function __construct(
        private Database $database
    ) {
    }

    public function create(array $data): array
    {
        $sql = '
            INSERT INTO listing_images (
                listing_id,
                file_path,
                original_filename,
                mime_type,
                file_size,
                sort_order
            )
            VALUES (
                :listing_id,
                :file_path,
                :original_filename,
                :mime_type,
                :file_size,
                :sort_order
            )
            RETURNING
                id,
                listing_id,
                file_path,
                original_filename,
                mime_type,
                file_size,
                sort_order,
                created_at
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'listing_id'        => $data['listing_id'],
            'file_path'         => $data['file_path'],
            'original_filename' => $data['original_filename'] ?? null,
            'mime_type'         => $data['mime_type'],
            'file_size'         => $data['file_size'],
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function findByListingId(int $listingId): array
    {
        $sql = '
            SELECT
                id,
                listing_id,
                file_path,
                original_filename,
                mime_type,
                file_size,
                sort_order,
                created_at
            FROM listing_images
            WHERE listing_id = :listing_id
            ORDER BY sort_order ASC, id ASC
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'listing_id' => $listingId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT
                id,
                listing_id,
                file_path,
                original_filename,
                mime_type,
                file_size,
                sort_order,
                created_at
            FROM listing_images
            WHERE id = :id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $sql = '
            DELETE FROM listing_images
            WHERE id = :id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public function findByIdForListingOwner(
        int $imageId,
        int $listingId,
        int $userId
    ): ?array {
        $sql = '
            SELECT
                li.id,
                li.listing_id,
                li.file_path,
                li.original_filename,
                li.mime_type,
                li.file_size,
                li.sort_order,
                li.created_at
            FROM listing_images li
            INNER JOIN listings l
                ON l.id = li.listing_id
            WHERE li.id = :image_id
            AND li.listing_id = :listing_id
            AND l.user_id = :user_id
        ';

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute([
            'image_id'   => $imageId,
            'listing_id' => $listingId,
            'user_id'    => $userId,
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}