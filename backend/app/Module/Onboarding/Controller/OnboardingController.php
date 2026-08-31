<?php

declare(strict_types=1);

namespace App\Module\Onboarding\Controller;

use App\DTO\BusinessId;
use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Onboarding\OnboardingFacade;
use App\Security\AuthContext;
use App\Security\JwtService;
use App\Security\RefreshTokenService;

final class OnboardingController
{
    private OnboardingFacade $facade;
    private JwtService $jwtService;
    private RefreshTokenService $refreshTokenService;

    public function __construct(
        ?OnboardingFacade $facade = null,
        ?JwtService $jwtService = null,
        ?RefreshTokenService $refreshTokenService = null,
    ) {
        $this->facade = $facade ?? new OnboardingFacade();
        $this->jwtService = $jwtService ?? new JwtService();
        $this->refreshTokenService = $refreshTokenService ?? new RefreshTokenService();
    }

    public function getDraft(): void
    {
        $sessionDraftId = $this->resolveSessionDraftId();
        if ($sessionDraftId === null) {
            ApiResponse::error('Missing session_draft_id header or query param', 'MISSING_DRAFT_ID', 400);
            return;
        }

        $draft = $this->facade->getDraft($sessionDraftId);
        ApiResponse::json([
            'session_draft_id' => $sessionDraftId,
            'draft' => $draft ?: new \stdClass(),
        ]);
    }

    public function updateDraft(): void
    {
        $sessionDraftId = $this->resolveSessionDraftId() ?? ('draft_' . bin2hex(random_bytes(16)));
        $body = JsonRequest::getJsonBody();

        $this->facade->saveDraft($sessionDraftId, $body);

        ApiResponse::json([
            'session_draft_id' => $sessionDraftId,
            'status' => 'saved',
        ]);
    }

    public function claimDraft(): void
    {
        $body = JsonRequest::getJsonBody();
        $sessionDraftId = (string) ($body['session_draft_id'] ?? ($this->resolveSessionDraftId() ?? ''));

        if ($sessionDraftId === '') {
            ApiResponse::error('session_draft_id is required to claim a draft', 'MISSING_DRAFT_ID', 422);
            return;
        }

        // Check if user is authenticated or if we need to issue new auth
        $businessId = AuthContext::getOptionalAuth();
        if ($businessId === null) {
            $businessId = BusinessId::generate();
        }

        $email = isset($body['email']) ? (string) $body['email'] : null;

        try {
            $business = $this->facade->claimDraft($sessionDraftId, $businessId, $email);

            $accessToken = $this->jwtService->createAccessToken($businessId);
            $refreshToken = $this->refreshTokenService->createRefreshToken($businessId);

            ApiResponse::created([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'business' => $business->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'DRAFT_CLAIM_ERROR', 422);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to claim draft: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    private function resolveSessionDraftId(): ?string
    {
        $header = JsonRequest::getHeader('session_draft_id') ?? JsonRequest::getHeader('X-Draft-Id');
        if ($header !== null && trim($header) !== '') {
            return trim($header);
        }

        $query = JsonRequest::getQueryParams();
        if (!empty($query['session_draft_id'])) {
            return (string) $query['session_draft_id'];
        }

        return null;
    }
}
