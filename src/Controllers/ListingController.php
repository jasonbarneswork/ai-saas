<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Database;
use App\Repositories\ListingRepository;
use App\Repositories\ListingImageRepository;
use App\Repositories\CategoryRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Resources\ListingResource;
use App\Validation\ListingValidator;

class ListingController
{
    public function index(
        Request $request,
        Response $response
    ): Response {
        $params = $request->getQueryParams();

        $filters = [
            'category_id' => $params['category_id'] ?? null,
            'search'      => trim($params['search'] ?? ''),
            'min_price'   => $params['min_price'] ?? null,
            'max_price'   => $params['max_price'] ?? null,
            'page'        => $params['page'] ?? 1,
            'limit'       => $params['limit'] ?? 20,
        ];

        if (
            $filters['min_price'] !== null &&
            !is_numeric($filters['min_price'])
        ) {
            return $this->json(
                $response,
                [
                    'error' => 'min_price must be numeric.'
                ],
                422
            );
        }

        if (
            $filters['max_price'] !== null &&
            !is_numeric($filters['max_price'])
        ) {
            return $this->json(
                $response,
                [
                    'error' => 'max_price must be numeric.'
                ],
                422
            );
        }

        $repository = new ListingRepository(
            new Database()
        );

        $result = $repository->findAll($filters);

        return $this->json(
            $response,
            [
                'data' => array_map(
                    [ListingResource::class, 'transform'],
                    $result['items']
                ),
                'meta' => [
                    'page'        => $result['page'],
                    'limit'       => $result['limit'],
                    'total'       => $result['total'],
                    'total_pages' => $result['total_pages'],
                ]
            ]
        );
    }

    public function show(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid listing ID.'
                ],
                400
            );
        }

        $repository = new ListingRepository(
            new Database()
        );

        $listing = $repository->findById($id);

        if ($listing === null) {
            return $this->json(
                $response,
                [
                    'error' => 'Listing not found.'
                ],
                404
            );
        }

        return $this->json(
            $response,
            [
                'data' => ListingResource::transform($listing)
            ]
        );
    }

    public function create(
        Request $request,
        Response $response
    ): Response {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            $body = json_decode(
                (string) $request->getBody(),
                true
            );
        }

        if (!is_array($body)) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid JSON request body.'
                ],
                400
            );
        }

        $errors = ListingValidator::validate($body);

        if ($errors) {
            return $this->json(
                $response,
                [
                    'error'  => 'Validation failed.',
                    'errors' => $errors,
                ],
                422
            );
        }

        $categoryRepository = new CategoryRepository(
            new Database()
        );

        $category = $categoryRepository->findById(
            (int) $body['category_id']
        );

        if ($category === null) {
            return $this->json(
                $response,
                [
                    'error' => 'Category not found.',
                ],
                422
            );
        }
        
        /*
        * The authenticated user comes from AuthMiddleware.
        * Never trust user_id supplied by the client.
        */
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

        /*
        * Override any user_id that may have been supplied.
        * The authenticated user's ID is authoritative.
        */
        $body['user_id'] = (int) $user['id'];

        $repository = new ListingRepository(
            new Database()
        );

        try {
            $listing = $repository->create($body);

            return $this->json(
                $response,
                [
                    'data' => $listing
                ],
                201
            );
        } catch (\PDOException $exception) {
            return $this->json(
                $response,
                [
                    'error'   => 'Unable to create listing.',
                    'message' => $exception->getMessage()
                ],
                400
            );
        }
    }

    public function update(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
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

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            $body = json_decode(
                (string) $request->getBody(),
                true
            );
        }

        if (!is_array($body)) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid JSON request body.'
                ],
                400
            );
        }

        $errors = ListingValidator::validate($body);

        if ($errors) {
            return $this->json(
                $response,
                [
                    'error'  => 'Validation failed.',
                    'errors' => $errors,
                ],
                422
            );
        }

        $categoryRepository = new CategoryRepository(
            new Database()
        );

        $category = $categoryRepository->findById(
            (int) $body['category_id']
        );

        if ($category === null) {
            return $this->json(
                $response,
                [
                    'error' => 'Category not found.',
                ],
                422
            );
        }

        $repository = new ListingRepository(
            new Database()
        );

        try {
            $listing = $repository->update(
                $id,
                (int) $user['id'],
                $body
            );

            if ($listing === null) {
                return $this->json(
                    $response,
                    [
                        'error' => 'Listing not found or you do not own this listing.'
                    ],
                    404
                );
            }

            return $this->json(
                $response,
                [
                    'data' => $listing
                ]
            );
        } catch (\PDOException $exception) {
            return $this->json(
                $response,
                [
                    'error'   => 'Unable to update listing.',
                    'message' => $exception->getMessage()
                ],
                400
            );
        }
    }

    public function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
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

        $database = new Database();

        $listingRepository = new ListingRepository($database);
        $imageRepository   = new ListingImageRepository($database);

        /*
        * First verify that the listing exists and belongs
        * to the authenticated user.
        */
        $listing = $listingRepository->findByIdForUser(
            $id,
            (int) $user['id']
        );

        if ($listing === null) {
            return $this->json(
                $response,
                [
                    'error' => 'Listing not found or you do not own this listing.'
                ],
                404
            );
        }

        /*
        * Get the image records before deleting the listing.
        * The database will remove these records automatically
        * through ON DELETE CASCADE.
        */
        $images = $imageRepository->findByListingId($id);

        /*
        * Delete the listing using the ownership-protected
        * repository method.
        */
        $deleted = $listingRepository->delete(
            $id,
            (int) $user['id']
        );

        if (!$deleted) {
            return $this->json(
                $response,
                [
                    'error' => 'Unable to delete listing.'
                ],
                500
            );
        }

        /*
        * The database deletion succeeded.
        * Remove the physical image files.
        */
        foreach ($images as $image) {
            $absolutePath = dirname(__DIR__, 2)
                . '/' . $image['file_path'];

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }

        /*
        * Remove the listing-specific upload directory
        * if it is now empty.
        */
        $listingDirectory = dirname(__DIR__, 2)
            . '/storage/uploads/listings/' . $id;

        if (is_dir($listingDirectory)) {
            $remainingFiles = scandir($listingDirectory);

            if (
                $remainingFiles !== false &&
                count($remainingFiles) === 2
            ) {
                rmdir($listingDirectory);
            }
        }

        return $response->withStatus(204);
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
