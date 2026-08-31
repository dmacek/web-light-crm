<?php

declare(strict_types=1);

namespace App\Http;

final class JsonRequest
{
    /**
     * @return array<string, mixed>
     */
    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (\JsonException $e) {
            ApiResponse::error('Invalid JSON payload in request body: ' . $e->getMessage(), 'INVALID_JSON', 400);
            exit;
        }
    }

    public static function getHeader(string $name): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$normalized])) {
            return (string) $_SERVER[$normalized];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    public static function getBearerToken(): ?string
    {
        $auth = self::getHeader('Authorization');
        if ($auth === null) {
            return null;
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getQueryParams(): array
    {
        return $_GET;
    }
}
