<?php

declare(strict_types=1);

use App\Database\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\CategoryController;
use App\Controllers\ListingController;
use App\Controllers\AuthController;
use App\Controllers\ListingImageController;
use App\Controllers\FavoriteController;
use App\Middleware\AuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->get('/api/health', function (Request $request, Response $response): Response {
    $data = [
        'status'      => 'ok',
        'application' => 'AI SaaS Platform',
        'php_version' => PHP_VERSION,
        'slim'        => '4.x',
    ];

    $response->getBody()->write(
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/db-health', function (Request $request, Response $response): Response {
    try {
        $database = new Database();

        $database->getConnection()->query('SELECT 1');

        $data = [
            'status'   => 'ok',
            'database' => 'connected',
            'engine'   => 'PostgreSQL',
        ];

        $statusCode = 200;
    } catch (Throwable $exception) {
        $data = [
            'status'   => 'error',
            'database' => 'connection failed',
            'message'  => $exception->getMessage(),
        ];

        $statusCode = 500;
    }

    $response->getBody()->write(
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($statusCode);
});

// Listing API
$app->post('/api/listings', [
    ListingController::class,
    'create'
])->add(new AuthMiddleware());

$app->get('/api/listings', [
    ListingController::class,
    'index'
]);

$app->get('/api/listings/{id}', [
    ListingController::class,
    'show'
]);

$app->put('/api/listings/{id}', [
    ListingController::class,
    'update'
])->add(new AuthMiddleware());

$app->delete('/api/listings/{id}', [
    ListingController::class,
    'delete'
])->add(new AuthMiddleware());

// Images API
$app->get('/api/listings/{id}/images', [
    ListingImageController::class,
    'index'
]);

$app->get('/api/listings/{id}/images/{imageId}', [
    ListingImageController::class,
    'show'
]);

$app->post('/api/listings/{id}/images', [
    ListingImageController::class,
    'create'
])->add(new AuthMiddleware());

$app->delete('/api/listings/{id}/images/{imageId}', [
    ListingImageController::class,
    'delete'
])->add(new AuthMiddleware());

// User API
$app->post('/api/auth/register', function (
    Request $request,
    Response $response
): Response {
    $controller = new AuthController();

    return $controller->register(
        $request,
        $response
    );
});

$app->post('/api/auth/login', function (
    Request $request,
    Response $response
): Response {
    $controller = new AuthController();

    return $controller->login(
        $request,
        $response
    );
});

$app->get('/api/me', function (
    Request $request,
    Response $response
): Response {
    $user = $request->getAttribute('user');

    $response->getBody()->write(
        json_encode(
            ['data' => $user],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        )
    );

    return $response->withHeader(
        'Content-Type',
        'application/json'
    );
})->add(new AuthMiddleware());


// Categories API
$app->get('/api/categories', [
    CategoryController::class,
    'index'
]);

$app->get('/api/categories/{id}', [
    CategoryController::class,
    'show'
]);

// Favorites API
$app->post(
    '/api/listings/{id}/favorite',
    [
        FavoriteController::class,
        'add'
    ]
)->add(new AuthMiddleware());

$app->delete(
    '/api/listings/{id}/favorite',
    [
        FavoriteController::class,
        'remove'
    ]
)->add(new AuthMiddleware());

$app->get(
    '/api/favorites',
    [
        FavoriteController::class,
        'index'
    ]
)->add(new AuthMiddleware());

//Publish API
$app->post('/api/listings/{id}/publish', 
    [
        ListingController::class,
        'publish'
    ]
)->add(new AuthMiddleware());

$app->post('/api/listings/{id}/unpublish', 
    [
        ListingController::class,
        'unpublish'
    ]
)->add(new AuthMiddleware());

$app->run();