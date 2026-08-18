<?php

declare(strict_types=1);

namespace App\Resources;

class ListingResource
{
    public static function transform(array $listing): array
    {
        return [
            'id'          => (int) $listing['id'],
            'title'       => $listing['title'],
            'description' => $listing['description'],
            'price'       => $listing['price'],
            'currency'    => $listing['currency'],
            'condition'   => $listing['condition'],
            'created_at'  => $listing['created_at'],
            'updated_at'  => $listing['updated_at'] ?? null,
            'category' => [
                'id'   => (int) $listing['category_id'],
                'name' => $listing['category_name'],
                'slug' => $listing['category_slug'],
            ],
            'seller' => [
                'id'   => (int) $listing['seller_id'],
                'name' => trim(
                    $listing['seller_first_name'] . ' ' .
                    $listing['seller_last_name']
                ),
            ],
        ];
    }
}
