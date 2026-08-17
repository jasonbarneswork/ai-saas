<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Database;
use App\Repositories\CategoryRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoryController
{
    public function index(
        Request $request,
        Response $response
    ): Response {
        $repository = new CategoryRepository(
            new Database()
        );

        $categories = $repository->findAll();

        $response->getBody()->write(
            json_encode(
                [
                    'data' => $categories
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function show(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            $response->getBody()->write(
                json_encode([
                    'error' => 'Invalid category ID.'
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $repository = new CategoryRepository(
            new Database()
        );

        $category = $repository->findById($id);

        if ($category === null) {
            $response->getBody()->write(
                json_encode([
                    'error' => 'Category not found.'
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(
            json_encode(
                ['data' => $category],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}
