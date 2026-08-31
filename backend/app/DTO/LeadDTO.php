<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\LeadStatus;

final readonly class LeadDTO
{
    public function __construct(
        public string $leadId,
        public BusinessId $businessId,
        public string $senderName,
        public string $senderPhone,
        public ?string $senderEmail,
        public string $message,
        public LeadStatus $status,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $reminderAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lead_id' => $this->leadId,
            'business_id' => $this->businessId->value,
            'sender_name' => $this->senderName,
            'sender_phone' => $this->senderPhone,
            'sender_email' => $this->senderEmail,
            'message' => $this->message,
            'status' => $this->status->value,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'reminder_at' => $this->reminderAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
