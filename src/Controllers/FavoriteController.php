<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Database;
use App\Repositories\FavoriteRepository;
use App\Repositories\ListingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FavoriteController
{
    public function add(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid listing ID.'
                ],
                400
            );
        }

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(
                $response,
                [
                    'error' => 'Authentication required.'
                ],
                401
            );
        }

        $userId = (int) $user['id'];

        $listingRepository = new ListingRepository(
            new Database()
        );

        $listing = $listingRepository->findById($listingId);

        if ($listing === null) {
            return $this->json(
                $response,
                [
                    'error' => 'Listing not found.'
                ],
                404
            );
        }

        $favoriteRepository = new FavoriteRepository(
            new Database()
        );

        $created = $favoriteRepository->create(
            $userId,
            $listingId
        );

        if (!$created) {
            return $this->json(
                $response,
                [
                    'data' => [
                        'listing_id' => $listingId,
                        'favorited' => true,
                    ]
                ]
            );
        }

        return $this->json(
            $response,
            [
                'data' => [
                    'listing_id' => $listingId,
                    'favorited' => true,
                ]
            ],
            201
        );
    }

    public function remove(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid listing ID.'
                ],
                400
            );
        }

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(
                $response,
                [
                    'error' => 'Authentication required.'
                ],
                401
            );
        }

        $userId = (int) $user['id'];

        $favoriteRepository = new FavoriteRepository(
            new Database()
        );

        $deleted = $favoriteRepository->delete(
            $userId,
            $listingId
        );

        if (!$deleted) {
            return $this->json(
                $response,
                [
                    'error' => 'Favorite not found.'
                ],
                404
            );
        }

        return $response->withStatus(204);
    }

    public function index(
        Request $request,
        Response $response
    ): Response {
        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(
                $response,
                [
                    'error' => 'Authentication required.'
                ],
                401
            );
        }

        $favoriteRepository = new FavoriteRepository(
            new Database()
        );

        $favorites = $favoriteRepository->findAllByUserId(
            (int) $user['id']
        );

        return $this->json(
            $response,
            [
                'data' => $favorites
            ]
        );
    }

    private function json(
        Response $response,
        array $data,
        int $status = 200
    ): Response {
        $response->getBody()->write(
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}