<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\AlumnoAuth;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return Response::error($response, 'Email y contraseña son requeridos', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return Response::error($response, 'Credenciales inválidas', 401, 'INVALID_CREDENTIALS');
        }

        $now = time();
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $expiry = (int)($_ENV['JWT_EXPIRY'] ?? 900);
        $refreshExpiry = (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $tokenId = bin2hex(random_bytes(16));

        $accessToken = JWT::encode([
            'sub' => $user['alumno_id'],
            'jti' => $tokenId,
            'iat' => $now,
            'exp' => $now + $expiry,
        ], $secret, 'HS256');

        $refreshToken = JWT::encode([
            'sub' => $user['alumno_id'],
            'jti' => $tokenId,
            'iat' => $now,
            'exp' => $now + $refreshExpiry,
            'type' => 'refresh',
        ], $secret, 'HS256');

        $alumnoAuth->updateRememberToken($user['alumno_id'], $tokenId);

        $nombre = trim(
            ($user['primer_nombre'] ?? '') . ' ' . ($user['segundo_nombre'] ?? '')
        );
        $apellido = trim(
            ($user['primer_apellido'] ?? '') . ' ' . ($user['segundo_apellido'] ?? '')
        );

        return Response::json($response, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiry,
            'user' => [
                'id' => $user['alumno_id'],
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $user['correo'],
                'ci_pasapote' => $user['ci_pasapote'],
            ],
        ]);
    }

    public function refresh(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return Response::error($response, 'Refresh token requerido', 422, 'VALIDATION_ERROR');
        }

        try {
            $secret = $_ENV['JWT_SECRET'] ?? '';
            $decoded = JWT::decode($refreshToken, new \Firebase\JWT\Key($secret, 'HS256'));

            if (!isset($decoded->type) || $decoded->type !== 'refresh') {
                return Response::error($response, 'Tipo de token inválido', 401, 'INVALID_TOKEN_TYPE');
            }

            $alumnoAuth = new AlumnoAuth();
            $storedToken = $alumnoAuth->findByAlumnoId($decoded->sub);

            if (!$storedToken || $storedToken['remember_token'] !== $decoded->jti) {
                return Response::error($response, 'Token revocado', 401, 'TOKEN_REVOKED');
            }

            $now = time();
            $expiry = (int)($_ENV['JWT_EXPIRY'] ?? 900);
            $newTokenId = bin2hex(random_bytes(16));

            $newAccessToken = JWT::encode([
                'sub' => $decoded->sub,
                'jti' => $newTokenId,
                'iat' => $now,
                'exp' => $now + $expiry,
            ], $secret, 'HS256');

            $alumnoAuth->updateRememberToken($decoded->sub, $newTokenId);

            return Response::json($response, [
                'access_token' => $newAccessToken,
                'expires_in' => $expiry,
            ]);
        } catch (\Exception $e) {
            return Response::error($response, 'Refresh token inválido o expirado', 401, 'INVALID_TOKEN');
        }
    }
}
