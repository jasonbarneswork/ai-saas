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
    public function create(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json($response, [
                'error' => 'Invalid listing ID.',
            ], 400);
        }

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json($response, [
                'error' => 'Authentication required.',
            ], 401);
        }

        $database = new Database();

        $listingRepository = new ListingRepository($database);
        $listing = $listingRepository->findById($listingId);

        if ($listing === null) {
            return $this->json($response, [
                'error' => 'Listing not found.',
            ], 404);
        }

        $favoriteRepository = new FavoriteRepository($database);

        $favoriteRepository->create(
            (int) $user['id'],
            $listingId
        );

        return $this->json($response, [
            'data' => [
                'listing_id' => $listingId,
                'favorited'  => true,
            ],
        ]);
    }

    public function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json($response, [
                'error' => 'Invalid listing ID.',
            ], 400);
        }

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json($response, [
                'error' => 'Authentication required.',
            ], 401);
        }

        $favoriteRepository = new FavoriteRepository(
            new Database()
        );

        $favoriteRepository->delete(
            (int) $user['id'],
            $listingId
        );

        return $response->withStatus(204);
    }

    public function index(
        Request $request,
        Response $response
    ): Response {
        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json($response, [
                'error' => 'Authentication required.',
            ], 401);
        }

        $favoriteRepository = new FavoriteRepository(
            new Database()
        );

        $favorites = $favoriteRepository->findAllByUserId(
            (int) $user['id']
        );

        return $this->json($response, [
            'data' => $favorites,
        ]);
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
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}