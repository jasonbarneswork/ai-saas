<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Database\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authorization = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $this->unauthorized('Authentication required.');
        }

        $token = trim($matches[1]);

        if ($token === '') {
            return $this->unauthorized('Authentication required.');
        }

        $tokenHash = hash('sha256', $token);

        try {
            $database = new Database();
            $pdo = $database->getConnection();

            $statement = $pdo->prepare(
                'SELECT
                    u.id,
                    u.email,
                    u.first_name,
                    u.last_name
                 FROM sessions s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.token_hash = :token_hash
                   AND s.expires_at > CURRENT_TIMESTAMP'
            );

            $statement->execute([
                'token_hash' => $tokenHash,
            ]);

            $user = $statement->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                return $this->unauthorized('Invalid or expired token.');
            }

            /*
             * Make the authenticated user available to downstream
             * routes through the request attributes.
             */
            $request = $request->withAttribute('user', [
                'id'         => (int) $user['id'],
                'email'      => $user['email'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
            ]);

            return $handler->handle($request);
        } catch (\Throwable $exception) {
            return $this->unauthorized('Authentication failed.');
        }
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response(401);

        $response->getBody()->write(
            json_encode(
                ['error' => $message],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}
