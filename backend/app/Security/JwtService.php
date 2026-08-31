<?php

declare(strict_types=1);

namespace App\Security;

use App\DTO\BusinessId;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    private string $secret;
    private string $algorithm = 'HS256';

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?: (getenv('JWT_SECRET') ?: 'dev_super_secret_jwt_key_at_least_32_chars_long');
    }

    /**
     * Issue an Access Token valid for 15 minutes
     */
    public function createAccessToken(BusinessId $businessId, int $ttlSeconds = 900): string
    {
        $now = time();
        $payload = [
            'iss' => 'web-light-crm-api',
            'sub' => $businessId->value,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Verify Access Token and return the BusinessId
     */
    public function verifyAccessToken(string $token): ?BusinessId
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            if (!isset($decoded->sub) || !is_string($decoded->sub)) {
                return null;
            }

            return new BusinessId($decoded->sub);
        } catch (\Throwable) {
            return null;
        }
    }
}
