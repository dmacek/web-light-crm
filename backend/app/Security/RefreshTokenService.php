<?php

declare(strict_types=1);

namespace App\Security;

use App\Bootstrap;
use App\DTO\BusinessId;

final class RefreshTokenService
{
    private const int TTL_90_DAYS = 90 * 86400;
    private \Predis\Client $redis;

    public function __construct(?\Predis\Client $redis = null)
    {
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    /**
     * Create a new refresh token and store in Redis
     */
    public function createRefreshToken(BusinessId $businessId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->redis->setex("refresh_token:{$token}", self::TTL_90_DAYS, $businessId->value);
        return $token;
    }

    /**
     * Rotate refresh token: validate old token, delete it, and issue a new one
     *
     * @return array{business_id: BusinessId, new_refresh_token: string}|null
     */
    public function rotateRefreshToken(string $oldToken): ?array
    {
        $key = "refresh_token:{$oldToken}";
        $businessIdStr = $this->redis->get($key);

        if (!$businessIdStr || !is_string($businessIdStr)) {
            return null;
        }

        // Invalidate old token immediately (prevents replay attacks)
        $this->redis->del([$key]);

        $businessId = new BusinessId($businessIdStr);
        $newToken = $this->createRefreshToken($businessId);

        return [
            'business_id' => $businessId,
            'new_refresh_token' => $newToken,
        ];
    }

    public function revoke(string $token): void
    {
        $this->redis->del(["refresh_token:{$token}"]);
    }
}
