<?php

declare(strict_types=1);

namespace App\Module\Crm;

use App\DTO\BusinessId;
use App\DTO\LeadDTO;
use App\Domain\LeadStatus;

final class LeadFacade
{
    private LeadRepository $leadRepository;

    public function __construct(?LeadRepository $leadRepository = null)
    {
        $this->leadRepository = $leadRepository ?? new LeadRepository();
    }

    /**
     * @return array{
     *     leads: list<array<string, mixed>>,
     *     stats: array{
     *         total: int,
     *         new: int,
     *         call_back: int,
     *         resolved: int,
     *         today: int
     *     }
     * }
     */
    public function getLeadsWithStats(BusinessId $businessId, ?LeadStatus $statusFilter = null): array
    {
        $allLeads = $this->leadRepository->findAllByBusinessId($businessId);

        $newCount = 0;
        $callBackCount = 0;
        $resolvedCount = 0;
        $todayCount = 0;

        $todayStr = (new \DateTimeImmutable())->format('Y-m-d');

        foreach ($allLeads as $lead) {
            if ($lead->status === LeadStatus::NEW) {
                $newCount++;
            } elseif ($lead->status === LeadStatus::CALL_BACK) {
                $callBackCount++;
            } elseif ($lead->status === LeadStatus::RESOLVED) {
                $resolvedCount++;
            }

            if ($lead->createdAt->format('Y-m-d') === $todayStr) {
                $todayCount++;
            }
        }

        $filtered = $statusFilter !== null
            ? array_filter($allLeads, static fn(LeadDTO $l): bool => $l->status === $statusFilter)
            : $allLeads;

        return [
            'leads' => array_values(array_map(static fn(LeadDTO $l): array => $l->toArray(), $filtered)),
            'stats' => [
                'total' => count($allLeads),
                'new' => $newCount,
                'call_back' => $callBackCount,
                'resolved' => $resolvedCount,
                'today' => $todayCount,
            ],
        ];
    }

    public function updateStatus(BusinessId $businessId, string $leadId, LeadStatus $newStatus): LeadDTO
    {
        $lead = $this->leadRepository->updateStatus($businessId, $leadId, $newStatus);
        if ($lead === null) {
            throw new \InvalidArgumentException("Lead with ID {$leadId} not found");
        }
        return $lead;
    }

    public function setReminder(BusinessId $businessId, string $leadId, ?\DateTimeImmutable $reminderAt): LeadDTO
    {
        $lead = $this->leadRepository->updateReminder($businessId, $leadId, $reminderAt);
        if ($lead === null) {
            throw new \InvalidArgumentException("Lead with ID {$leadId} not found");
        }
        return $lead;
    }
}
