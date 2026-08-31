<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\SubscriptionPlan;
use App\Domain\SubscriptionStatus;

final readonly class SubscriptionDTO
{
    public function __construct(
        public SubscriptionStatus $status,
        public ?SubscriptionPlan $plan,
        public \DateTimeImmutable $trialEndsAt,
        public ?\DateTimeImmutable $currentPeriodEndsAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'plan' => $this->plan?->value,
            'trial_ends_at' => $this->trialEndsAt->format(\DateTimeInterface::ATOM),
            'current_period_ends_at' => $this->currentPeriodEndsAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
