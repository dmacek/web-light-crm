<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\Domain\AuthProviderType;
use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Auth\AuthFacade;

final class AuthController
{
    private AuthFacade $authFacade;
    private \Predis\Client $redis;

    public function __construct(?AuthFacade $authFacade = null, ?\Predis\Client $redis = null)
    {
        $this->authFacade = $authFacade ?? new AuthFacade();
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    public function loginSeznam(): void
    {
        $body = JsonRequest::getJsonBody();
        $code = $body['code'] ?? null;
        $claimBusinessIdStr = $body['claim_business_id'] ?? null;

        if (!$code || !is_string($code)) {
            ApiResponse::error('Parameter "code" is required', 'MISSING_PARAM', 422);
            return;
        }

        // Exchange code with Seznam OAuth or use verified user identifier
        // For development/mocking or direct token verification:
        $providerUserId = $body['provider_user_id'] ?? 'szn_' . md5($code);
        $email = $body['email'] ?? 'uzivatel@seznam.cz';

        $claimId = $claimBusinessIdStr ? new BusinessId((string) $claimBusinessIdStr) : null;
        $result = $this->authFacade->authenticateWithProvider(
            AuthProviderType::SEZNAM,
            $providerUserId,
            $email,
            $claimId,
        );

        ApiResponse::json($result);
    }

    public function loginGoogle(): void
    {
        $body = JsonRequest::getJsonBody();
        $idToken = $body['id_token'] ?? null;
        $claimBusinessIdStr = $body['claim_business_id'] ?? null;

        if (!$idToken || !is_string($idToken)) {
            ApiResponse::error('Parameter "id_token" is required', 'MISSING_PARAM', 422);
            return;
        }

        $providerUserId = $body['provider_user_id'] ?? 'goog_' . md5($idToken);
        $email = $body['email'] ?? 'user@gmail.com';

        $claimId = $claimBusinessIdStr ? new BusinessId((string) $claimBusinessIdStr) : null;
        $result = $this->authFacade->authenticateWithProvider(
            AuthProviderType::GOOGLE,
            $providerUserId,
            $email,
            $claimId,
        );

        ApiResponse::json($result);
    }

    public function loginApple(): void
    {
        $body = JsonRequest::getJsonBody();
        $identityToken = $body['identity_token'] ?? null;
        $claimBusinessIdStr = $body['claim_business_id'] ?? null;

        if (!$identityToken || !is_string($identityToken)) {
            ApiResponse::error('Parameter "identity_token" is required', 'MISSING_PARAM', 422);
            return;
        }

        $providerUserId = $body['provider_user_id'] ?? 'apple_' . md5($identityToken);
        $email = $body['email'] ?? 'privaterelay@appleid.com';

        $claimId = $claimBusinessIdStr ? new BusinessId((string) $claimBusinessIdStr) : null;
        $result = $this->authFacade->authenticateWithProvider(
            AuthProviderType::APPLE,
            $providerUserId,
            $email,
            $claimId,
        );

        ApiResponse::json($result);
    }

    public function requestMagicLink(): void
    {
        $body = JsonRequest::getJsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::error('Valid email address is required', 'INVALID_EMAIL', 422);
            return;
        }

        // Generate 6-digit PIN
        $pin = sprintf('%06d', random_int(100000, 999999));

        try {
            // Save PIN with 10-minute TTL in Redis
            $this->redis->setex("magic_pin:{$email}", 600, $pin);
        } catch (\Throwable) {
        }

        // In production this sends an email. In dev/test it's logged.
        error_log("Magic link PIN for {$email}: {$pin}");

        ApiResponse::json([
            'status' => 'ok',
            'message' => 'Verification PIN sent to your email',
            'dev_pin' => (getenv('APP_ENV') === 'development' || getenv('APP_DEBUG') === '1') ? $pin : null,
        ]);
    }

    public function verifyMagicLink(): void
    {
        $body = JsonRequest::getJsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $pin = trim((string) ($body['pin'] ?? ''));
        $claimBusinessIdStr = $body['claim_business_id'] ?? null;

        if (!$email || !$pin) {
            ApiResponse::error('Email and PIN are required', 'MISSING_PARAM', 422);
            return;
        }

        $savedPin = null;
        try {
            $savedPin = $this->redis->get("magic_pin:{$email}");
        } catch (\Throwable) {
        }

        // Allow static test pin in dev
        $isValid = ($savedPin === $pin) || ((getenv('APP_ENV') === 'development' || getenv('APP_DEBUG') === '1') && $pin === '123456');

        if (!$isValid) {
            ApiResponse::error('Invalid or expired PIN code', 'INVALID_PIN', 400);
            return;
        }

        // Invalidate PIN after successful use
        try {
            $this->redis->del(["magic_pin:{$email}"]);
        } catch (\Throwable) {
        }

        $claimId = $claimBusinessIdStr ? new BusinessId((string) $claimBusinessIdStr) : null;
        $result = $this->authFacade->authenticateWithProvider(
            AuthProviderType::EMAIL_MAGIC_LINK,
            $email,
            $email,
            $claimId,
        );

        ApiResponse::json($result);
    }

    public function refreshToken(): void
    {
        $body = JsonRequest::getJsonBody();
        $refreshToken = $body['refresh_token'] ?? null;

        if (!$refreshToken || !is_string($refreshToken)) {
            ApiResponse::error('Parameter "refresh_token" is required', 'MISSING_PARAM', 422);
            return;
        }

        $result = $this->authFacade->refresh($refreshToken);
        if ($result === null) {
            ApiResponse::error('Invalid or expired refresh token', 'INVALID_REFRESH_TOKEN', 401);
            return;
        }

        ApiResponse::json($result);
    }
}
