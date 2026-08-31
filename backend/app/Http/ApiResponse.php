<?php

declare(strict_types=1);

namespace App\Http;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * @param array<string, mixed> $details
     */
    public static function error(string $message, string $code = 'ERROR', int $statusCode = 400, array $details = []): void
    {
        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if (!empty($details)) {
            $payload['error']['details'] = $details;
        }

        self::json($payload, $statusCode);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function created(array $data): void
    {
        self::json($data, 201);
    }

    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }
}
