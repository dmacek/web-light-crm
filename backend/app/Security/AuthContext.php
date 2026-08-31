<?php

declare(strict_types=1);

namespace App\Security;

use App\DTO\BusinessId;
use App\Http\ApiResponse;
use App\Http\JsonRequest;

final class AuthContext
{
    private static ?JwtService $jwtService = null;

    public static function requireAuth(): BusinessId
    {
        $token = JsonRequest::getBearerToken();
        if ($token === null) {
            ApiResponse::error('Missing Bearer authorization token', 'UNAUTHORIZED', 401);
            exit;
        }

        if (self::$jwtService === null) {
            self::$jwtService = new JwtService();
        }

        $businessId = self::$jwtService->verifyAccessToken($token);
        if ($businessId === null) {
            ApiResponse::error('Invalid or expired authorization token', 'INVALID_TOKEN', 401);
            exit;
        }

        return $businessId;
    }

    public static function getOptionalAuth(): ?BusinessId
    {
        $token = JsonRequest::getBearerToken();
        if ($token === null) {
            return null;
        }

        if (self::$jwtService === null) {
            self::$jwtService = new JwtService();
        }

        return self::$jwtService->verifyAccessToken($token);
    }
}
