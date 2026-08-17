<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Database;
use App\Repositories\ListingImageRepository;
use App\Repositories\ListingRepository;
use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListingImageController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function index(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json(
                $response,
                ['error' => 'Invalid listing ID.'],
                400
            );
        }

        $repository = new ListingImageRepository(
            new Database()
        );

        $images = $repository->findByListingId($listingId);

        return $this->json(
            $response,
            ['data' => $images]
        );
    }

    public function create(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);

        if ($listingId <= 0) {
            return $this->json(
                $response,
                ['error' => 'Invalid listing ID.'],
                400
            );
        }

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(
                $response,
                ['error' => 'Authentication required.'],
                401
            );
        }

        $listingRepository = new ListingRepository(
            new Database()
        );

        $listing = $listingRepository->findByIdForUser(
            $listingId,
            (int) $user['id']
        );

        if ($listing === null) {
            return $this->json(
                $response,
                ['error' => 'Listing not found or you do not own this listing.'],
                404
            );
        }

        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['image'])) {
            return $this->json(
                $response,
                ['error' => 'Image file is required.'],
                422
            );
        }

        $uploadedFile = $uploadedFiles['image'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $this->json(
                $response,
                ['error' => 'Image upload failed.'],
                422
            );
        }

        $fileSize = $uploadedFile->getSize();

        if ($fileSize === null || $fileSize <= 0) {
            return $this->json(
                $response,
                ['error' => 'Invalid image file size.'],
                422
            );
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            return $this->json(
                $response,
                ['error' => 'Image must not exceed 10 MB.'],
                422
            );
        }

        $mimeType = $uploadedFile->getClientMediaType();

        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            return $this->json(
                $response,
                ['error' => 'Unsupported image type. Allowed types: JPEG, PNG, WEBP.'],
                422
            );
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $relativeDirectory = 'storage/uploads/listings/' . $listingId;

        $absoluteDirectory = dirname(__DIR__, 2)
            . '/' . $relativeDirectory;

        if (!is_dir($absoluteDirectory)) {
            if (!mkdir($absoluteDirectory, 0755, true)) {
                return $this->json(
                    $response,
                    ['error' => 'Unable to create upload directory.'],
                    500
                );
            }
        }

        $uploadedFile->moveTo(
            $absoluteDirectory . '/' . $filename
        );

        $relativePath = $relativeDirectory . '/' . $filename;

        $repository = new ListingImageRepository(
            new Database()
        );

        $image = $repository->create([
            'listing_id'        => $listingId,
            'file_path'         => $relativePath,
            'original_filename' => $uploadedFile->getClientFilename(),
            'mime_type'         => $mimeType,
            'file_size'         => $fileSize,
            'sort_order'        => 0,
        ]);

        return $this->json(
            $response,
            ['data' => $image],
            201
        );
    }

    public function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);
        $imageId   = (int) ($args['imageId'] ?? 0);

        $user = $request->getAttribute('user');

        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(
                $response,
                ['error' => 'Authentication required.'],
                401
            );
        }

        if ($listingId <= 0 || $imageId <= 0) {
            return $this->json(
                $response,
                ['error' => 'Invalid listing or image ID.'],
                400
            );
        }

        $repository = new ListingImageRepository(
            new Database()
        );

        $image = $repository->findByIdForListingOwner(
            $imageId,
            $listingId,
            (int) $user['id']
        );

        if ($image === null) {
            return $this->json(
                $response,
                ['error' => 'Image not found or you do not own this listing.'],
                404
            );
        }

        $absolutePath = dirname(__DIR__, 2)
            . '/' . $image['file_path'];

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }

        $repository->delete($imageId);

        return $response->withStatus(204);
    }

    public function show(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $listingId = (int) ($args['id'] ?? 0);
        $imageId = (int) ($args['imageId'] ?? 0);

        if ($listingId <= 0 || $imageId <= 0) {
            return $this->json(
                $response,
                [
                    'error' => 'Invalid listing or image ID.'
                ],
                400
            );
        }

        $repository = new ListingImageRepository(
            new Database()
        );

        $image = $repository->findById($imageId);

        if (
            $image === null ||
            (int) $image['listing_id'] !== $listingId
        ) {
            return $this->json(
                $response,
                [
                    'error' => 'Image not found.'
                ],
                404
            );
        }

        $absolutePath = dirname(__DIR__, 2)
            . '/' . $image['file_path'];

        if (!is_file($absolutePath)) {
            return $this->json(
                $response,
                [
                    'error' => 'Image file not found.'
                ],
                404
            );
        }

        $stream = fopen($absolutePath, 'rb');

        if ($stream === false) {
            return $this->json(
                $response,
                [
                    'error' => 'Unable to read image file.'
                ],
                500
            );
        }

        $response = $response
            ->withHeader(
                'Content-Type',
                $image['mime_type']
            )
            ->withHeader(
                'Content-Length',
                (string) $image['file_size']
            )
            ->withHeader(
                'Content-Disposition',
                'inline; filename="' .
                addslashes(
                    $image['original_filename'] ?? 'image'
                ) .
                '"'
            );

        $response->getBody()->write(
            stream_get_contents($stream)
        );

        fclose($stream);

        return $response;
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