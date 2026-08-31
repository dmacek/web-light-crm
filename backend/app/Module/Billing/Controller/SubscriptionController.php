<?php

declare(strict_types=1);

namespace App\Module\Billing\Controller;

use App\Domain\SubscriptionPlan;
use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Billing\SubscriptionService;
use App\Security\AuthContext;

final class SubscriptionController
{
    private SubscriptionService $service;

    public function __construct(?SubscriptionService $service = null)
    {
        $this->service = $service ?? new SubscriptionService();
    }

    public function getSubscription(): void
    {
        $businessId = AuthContext::requireAuth();
        $sub = $this->service->getSubscription($businessId);

        if ($sub === null) {
            ApiResponse::error('Subscription record not found', 'NOT_FOUND', 404);
            return;
        }

        ApiResponse::json($sub->toArray());
    }

    public function subscribe(): void
    {
        $businessId = AuthContext::requireAuth();
        $body = JsonRequest::getJsonBody();

        $planStr = strtoupper((string) ($body['plan'] ?? 'MONTHLY'));
        $plan = SubscriptionPlan::tryFrom($planStr);

        if ($plan === null) {
            ApiResponse::error("Invalid plan '{$planStr}'. Allowed: MONTHLY, ANNUAL", 'INVALID_PLAN', 422);
            return;
        }

        $customDomain = isset($body['custom_domain']) ? (string) $body['custom_domain'] : null;

        try {
            $updated = $this->service->subscribe($businessId, $plan, $customDomain);
            ApiResponse::json([
                'status' => 'ok',
                'message' => 'Předplatné bylo úspěšně aktivováno.',
                'subscription' => $updated->toArray(),
            ]);
        } catch (\Throwable $e) {
            ApiResponse::error('Chyba při aktivaci předplatného: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}
