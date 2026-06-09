<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token no proporcionado',
                'code' => 'AUTH_REQUIRED',
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(401);
        }

        $token = substr($authHeader, 7);

        try {
            $secret = $_ENV['JWT_SECRET'] ?? '';
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token inválido o expirado',
                'code' => 'INVALID_TOKEN',
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(401);
        }

        $request = $request->withAttribute('alumno_id', $decoded->sub);
        $request = $request->withAttribute('auth_id', $decoded->jti ?? null);

        return $handler->handle($request);
    }
}
