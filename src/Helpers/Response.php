<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public static function json(
        ResponseInterface $response,
        mixed $data,
        int $status = 200,
        ?string $message = null,
        ?array $pagination = null
    ): ResponseInterface {
        $body = [
            'success' => $status >= 200 && $status < 300,
        ];

        if ($message !== null) {
            $body['message'] = $message;
        }

        if ($pagination !== null) {
            $body['data'] = $data;
            $body['pagination'] = $pagination;
        } else {
            $body['data'] = $data;
        }

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    public static function error(
        ResponseInterface $response,
        string $message,
        int $status = 400,
        string $code = 'ERROR'
    ): ResponseInterface {
        $body = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
