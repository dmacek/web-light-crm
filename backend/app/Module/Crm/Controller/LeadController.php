<?php

declare(strict_types=1);

namespace App\Module\Crm\Controller;

use App\Domain\LeadStatus;
use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Crm\LeadFacade;
use App\Security\AuthContext;

final class LeadController
{
    private LeadFacade $facade;

    public function __construct(?LeadFacade $facade = null)
    {
        $this->facade = $facade ?? new LeadFacade();
    }

    public function list(): void
    {
        $businessId = AuthContext::requireAuth();

        $query = JsonRequest::getQueryParams();
        $statusStr = !empty($query['status']) ? strtoupper((string) $query['status']) : null;
        $status = $statusStr ? LeadStatus::tryFrom($statusStr) : null;

        $result = $this->facade->getLeadsWithStats($businessId, $status);
        ApiResponse::json($result);
    }

    public function updateStatus(string $leadId): void
    {
        $businessId = AuthContext::requireAuth();
        $body = JsonRequest::getJsonBody();

        $statusStr = strtoupper((string) ($body['status'] ?? ''));
        $newStatus = LeadStatus::tryFrom($statusStr);

        if ($newStatus === null) {
            ApiResponse::error("Invalid status value '{$statusStr}'. Allowed values: NEW, CALL_BACK, RESOLVED", 'INVALID_STATUS', 422);
            return;
        }

        try {
            $updated = $this->facade->updateStatus($businessId, $leadId, $newStatus);
            ApiResponse::json($updated->toArray());
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'NOT_FOUND', 404);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to update lead status: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    public function updateReminder(string $leadId): void
    {
        $businessId = AuthContext::requireAuth();
        $body = JsonRequest::getJsonBody();

        $reminderStr = $body['reminder_at'] ?? null;
        $reminderAt = null;

        if ($reminderStr !== null && is_string($reminderStr) && trim($reminderStr) !== '') {
            try {
                $reminderAt = new \DateTimeImmutable($reminderStr);
            } catch (\Throwable) {
                ApiResponse::error('Invalid ISO datetime format for reminder_at', 'INVALID_DATETIME', 422);
                return;
            }
        }

        try {
            $updated = $this->facade->setReminder($businessId, $leadId, $reminderAt);
            ApiResponse::json($updated->toArray());
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'NOT_FOUND', 404);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to set reminder: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}
