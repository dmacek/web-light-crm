<?php

declare(strict_types=1);

namespace App\Module\Crm;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\DTO\LeadDTO;
use App\Domain\LeadStatus;

final class LeadRepository
{
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
    }

    /**
     * @return list<LeadDTO>
     */
    public function findAllByBusinessId(BusinessId $businessId, ?LeadStatus $status = null): array
    {
        $sql = <<<'SQL'
            SELECT lead_id, business_id, sender_name, sender_phone, sender_email,
                   message, status, reminder_at, created_at, updated_at
            FROM leads
            WHERE business_id = :business_id
        SQL;

        $params = ['business_id' => $businessId->value];
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status->value;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row): LeadDTO => $this->hydrateLead($row), $rows);
    }

    public function findById(BusinessId $businessId, string $leadId): ?LeadDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT lead_id, business_id, sender_name, sender_phone, sender_email,
                   message, status, reminder_at, created_at, updated_at
            FROM leads
            WHERE business_id = :business_id AND lead_id = :lead_id
        SQL);
        $stmt->execute([
            'business_id' => $businessId->value,
            'lead_id' => $leadId,
        ]);
        $row = $stmt->fetch();

        return $row ? $this->hydrateLead($row) : null;
    }

    public function create(
        BusinessId $businessId,
        string $senderName,
        string $senderPhone,
        ?string $senderEmail,
        string $message,
    ): LeadDTO {
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO leads (
                business_id, sender_name, sender_phone, sender_email, message, status
            ) VALUES (
                :business_id, :sender_name, :sender_phone, :sender_email, :message, 'NEW'
            ) RETURNING lead_id, business_id, sender_name, sender_phone, sender_email,
                        message, status, reminder_at, created_at, updated_at
        SQL);

        $stmt->execute([
            'business_id' => $businessId->value,
            'sender_name' => $senderName,
            'sender_phone' => $senderPhone,
            'sender_email' => $senderEmail,
            'message' => $message,
        ]);

        $row = $stmt->fetch();
        return $this->hydrateLead($row);
    }

    public function updateStatus(BusinessId $businessId, string $leadId, LeadStatus $status): ?LeadDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            UPDATE leads
            SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE business_id = :business_id AND lead_id = :lead_id
            RETURNING lead_id, business_id, sender_name, sender_phone, sender_email,
                      message, status, reminder_at, created_at, updated_at
        SQL);

        $stmt->execute([
            'business_id' => $businessId->value,
            'lead_id' => $leadId,
            'status' => $status->value,
        ]);

        $row = $stmt->fetch();
        return $row ? $this->hydrateLead($row) : null;
    }

    public function updateReminder(BusinessId $businessId, string $leadId, ?\DateTimeImmutable $reminderAt): ?LeadDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            UPDATE leads
            SET reminder_at = :reminder_at, updated_at = CURRENT_TIMESTAMP
            WHERE business_id = :business_id AND lead_id = :lead_id
            RETURNING lead_id, business_id, sender_name, sender_phone, sender_email,
                      message, status, reminder_at, created_at, updated_at
        SQL);

        $stmt->execute([
            'business_id' => $businessId->value,
            'lead_id' => $leadId,
            'reminder_at' => $reminderAt?->format(\DateTimeInterface::ATOM),
        ]);

        $row = $stmt->fetch();
        return $row ? $this->hydrateLead($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateLead(array $row): LeadDTO
    {
        return new LeadDTO(
            leadId: (string) $row['lead_id'],
            businessId: new BusinessId((string) $row['business_id']),
            senderName: (string) $row['sender_name'],
            senderPhone: (string) $row['sender_phone'],
            senderEmail: $row['sender_email'] !== null ? (string) $row['sender_email'] : null,
            message: (string) $row['message'],
            status: LeadStatus::from((string) $row['status']),
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            reminderAt: !empty($row['reminder_at']) ? new \DateTimeImmutable((string) $row['reminder_at']) : null,
        );
    }
}
