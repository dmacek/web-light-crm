<?php

declare(strict_types=1);

namespace App\Module\Auth;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\Domain\AuthProviderType;
use App\Module\Domain\BusinessRepository;
use App\Security\JwtService;
use App\Security\RefreshTokenService;

final class AuthFacade
{
    private JwtService $jwtService;
    private RefreshTokenService $refreshTokenService;
    private AuthProviderRepository $authProviderRepository;
    private BusinessRepository $businessRepository;

    public function __construct(
        ?JwtService $jwtService = null,
        ?RefreshTokenService $refreshTokenService = null,
        ?AuthProviderRepository $authProviderRepository = null,
        ?BusinessRepository $businessRepository = null,
    ) {
        $this->jwtService = $jwtService ?? new JwtService();
        $this->refreshTokenService = $refreshTokenService ?? new RefreshTokenService();
        $this->authProviderRepository = $authProviderRepository ?? new AuthProviderRepository();
        $this->businessRepository = $businessRepository ?? new BusinessRepository();
    }

    /**
     * Authenticate or link business with provider
     *
     * @return array{
     *     access_token: string,
     *     refresh_token: string,
     *     business_id: string,
     *     is_new_business: bool,
     *     business?: array<string, mixed>
     * }
     */
    public function authenticateWithProvider(
        AuthProviderType $provider,
        string $providerUserId,
        ?string $email = null,
        ?BusinessId $claimBusinessId = null,
    ): array {
        // 1. Check if provider account already exists
        $existing = $this->authProviderRepository->findByProvider($provider, $providerUserId);

        if ($existing !== null) {
            $businessId = new BusinessId($existing['business_id']);
            $business = $this->businessRepository->findById($businessId);

            $accessToken = $this->jwtService->createAccessToken($businessId);
            $refreshToken = $this->refreshTokenService->createRefreshToken($businessId);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'business_id' => $businessId->value,
                'is_new_business' => false,
                'business' => $business?->toArray(),
            ];
        }

        // 2. If claiming into an existing/draft business
        if ($claimBusinessId !== null) {
            $this->authProviderRepository->link($claimBusinessId, $provider, $providerUserId);
            $business = $this->businessRepository->findById($claimBusinessId);

            $accessToken = $this->jwtService->createAccessToken($claimBusinessId);
            $refreshToken = $this->refreshTokenService->createRefreshToken($claimBusinessId);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'business_id' => $claimBusinessId->value,
                'is_new_business' => false,
                'business' => $business?->toArray(),
            ];
        }

        // 3. New account placeholder
        $newBusinessId = BusinessId::generate();
        $this->authProviderRepository->link($newBusinessId, $provider, $providerUserId);

        $accessToken = $this->jwtService->createAccessToken($newBusinessId);
        $refreshToken = $this->refreshTokenService->createRefreshToken($newBusinessId);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'business_id' => $newBusinessId->value,
            'is_new_business' => true,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, business_id: string}|null
     */
    public function refresh(string $oldRefreshToken): ?array
    {
        $result = $this->refreshTokenService->rotateRefreshToken($oldRefreshToken);
        if ($result === null) {
            return null;
        }

        $businessId = $result['business_id'];
        $accessToken = $this->jwtService->createAccessToken($businessId);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $result['new_refresh_token'],
            'business_id' => $businessId->value,
        ];
    }
}
