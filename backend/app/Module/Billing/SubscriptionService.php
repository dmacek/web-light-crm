<?php

declare(strict_types=1);

namespace App\Module\Billing;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\DTO\SubscriptionDTO;
use App\Domain\CustomDomainStatus;
use App\Domain\SubscriptionPlan;
use App\Domain\SubscriptionStatus;
use App\Module\Domain\BusinessRepository;
use App\Module\Integrations\Wedos\WedosClient;

final class SubscriptionService
{
    private \PDO $pdo;
    private BusinessRepository $businessRepository;
    private WedosClient $wedosClient;

    public function __construct(
        ?\PDO $pdo = null,
        ?BusinessRepository $businessRepository = null,
        ?WedosClient $wedosClient = null,
    ) {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
        $this->businessRepository = $businessRepository ?? new BusinessRepository();
        $this->wedosClient = $wedosClient ?? new WedosClient();
    }

    public function getSubscription(BusinessId $businessId): ?SubscriptionDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscriptions WHERE business_id = :id');
        $stmt->execute(['id' => $businessId->value]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new SubscriptionDTO(
            status: SubscriptionStatus::from((string) $row['status']),
            plan: !empty($row['plan']) ? SubscriptionPlan::from((string) $row['plan']) : null,
            trialEndsAt: new \DateTimeImmutable((string) $row['trial_ends_at']),
            currentPeriodEndsAt: !empty($row['current_period_ends_at']) ? new \DateTimeImmutable((string) $row['current_period_ends_at']) : null,
        );
    }

    /**
     * Activate subscription (Mock payment flow per ASSIGNMENT §4 Modul 5)
     */
    public function subscribe(BusinessId $businessId, SubscriptionPlan $plan, ?string $customDomain = null): SubscriptionDTO
    {
        $days = ($plan === SubscriptionPlan::ANNUAL) ? 365 : 30;
        $periodEnds = (new \DateTimeImmutable("+{$days} days"))->format(\DateTimeInterface::ATOM);

        $stmt = $this->pdo->prepare(<<<'SQL'
            UPDATE subscriptions
            SET status = 'ACTIVE',
                plan = :plan,
                current_period_ends_at = :period_ends,
                updated_at = CURRENT_TIMESTAMP
            WHERE business_id = :id
            RETURNING *
        SQL);

        $stmt->execute([
            'id' => $businessId->value,
            'plan' => $plan->value,
            'period_ends' => $periodEnds,
        ]);

        $row = $stmt->fetch();

        // If Annual plan + custom domain requested -> trigger Wedos registration
        if ($plan === SubscriptionPlan::ANNUAL && $customDomain !== null && trim($customDomain) !== '') {
            $domain = strtolower(trim($customDomain));
            $business = $this->businessRepository->findById($businessId);

            if ($business !== null) {
                $regResult = $this->wedosClient->registerCzDomain(
                    domain: $domain,
                    companyName: $business->profile->companyName,
                    email: $business->email,
                    phone: $business->phone,
                );

                $domainStatus = CustomDomainStatus::tryFrom($regResult['status']) ?? CustomDomainStatus::ACTIVE;
                $this->businessRepository->updateCustomDomain($businessId, $domain, $domainStatus);
            }
        }

        return new SubscriptionDTO(
            status: SubscriptionStatus::ACTIVE,
            plan: $plan,
            trialEndsAt: new \DateTimeImmutable((string) $row['trial_ends_at']),
            currentPeriodEndsAt: new \DateTimeImmutable($periodEnds),
        );
    }
}
