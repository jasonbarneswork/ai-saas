<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AuthController
{
    public function register(
        Request $request,
        Response $response
    ): Response {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json(
                $response,
                ['error' => 'Invalid request body.'],
                400
            );
        }

        $email     = strtolower(trim((string) ($body['email'] ?? '')));
        $password  = (string) ($body['password'] ?? '');
        $firstName = trim((string) ($body['first_name'] ?? ''));
        $lastName  = trim((string) ($body['last_name'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(
                $response,
                ['error' => 'A valid email address is required.'],
                422
            );
        }

        if (strlen($password) < 8) {
            return $this->json(
                $response,
                ['error' => 'Password must be at least 8 characters.'],
                422
            );
        }

        $database = new Database();
        $pdo = $database->getConnection();

        $check = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email'
        );

        $check->execute([
            'email' => $email
        ]);

        if ($check->fetch()) {
            return $this->json(
                $response,
                ['error' => 'An account with this email already exists.'],
                409
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $statement = $pdo->prepare(
            'INSERT INTO users
                (email, password_hash, first_name, last_name)
             VALUES
                (:email, :password_hash, :first_name, :last_name)
             RETURNING id, email, first_name, last_name, created_at'
        );

        $statement->execute([
            'email'         => $email,
            'password_hash' => $passwordHash,
            'first_name'    => $firstName !== '' ? $firstName : null,
            'last_name'     => $lastName  !== '' ? $lastName  : null,
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->json(
            $response,
            [
                'data' => [
                    'id'         => (int) $user['id'],
                    'email'      => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name'  => $user['last_name'],
                    'created_at' => $user['created_at'],
                ]
            ],
            201
        );
    }

    public function login(
        Request  $request,
        Response $response
    ): Response {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json(
                $response,
                ['error' => 'Invalid request body.'],
                400
            );
        }

        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return $this->json(
                $response,
                ['error' => 'Valid email is required.'],
                422
            );
        }

        if ($password === '') {
            return $this->json(
                $response,
                ['error' => 'Password is required.'],
                422
            );
        }

        $database = new Database();
        $pdo = $database->getConnection();

        $statement = $pdo->prepare(
            'SELECT id, email, password_hash, first_name, last_name
            FROM users
            WHERE email = :email'
        );

        $statement->execute([
            'email' => $email
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (
            !$user ||
            !password_verify($password, $user['password_hash'])
        ) {
            return $this->json(
                $response,
                ['error' => 'Invalid email or password.'],
                401
            );
        }

        // Generate a secure random token.
        $token = bin2hex(random_bytes(32));

        // Store only the token hash in the database.
        $tokenHash = hash('sha256', $token);

        // Token expires in 30 days.
        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + (30 * 24 * 60 * 60)
        );

        $session = $pdo->prepare(
            'INSERT INTO sessions
                (user_id, token_hash, expires_at)
            VALUES
                (:user_id, :token_hash, :expires_at)'
        );

        $session->execute([
            'user_id'    => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return $this->json(
            $response,
            [
                'data' => [
                    'token'      => $token,
                    'expires_at' => $expiresAt,
                    'user'       => [
                        'id'         => (int) $user['id'],
                        'email'      => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name'  => $user['last_name'],
                    ],
                ]
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
