<?php

declare(strict_types=1);

namespace App\Module\Domain;

use App\Bootstrap;
use App\DTO\BusinessDTO;
use App\DTO\BusinessId;
use App\DTO\BusinessProfileDTO;
use App\DTO\SubscriptionDTO;
use App\Domain\Archetype;
use App\Domain\AuthProviderType;
use App\Domain\CustomDomainStatus;
use App\Domain\SubscriptionPlan;
use App\Domain\SubscriptionStatus;

final class BusinessRepository
{
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
    }

    public function findById(BusinessId $id): ?BusinessDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT b.*, 
                   s.status AS sub_status, s.plan AS sub_plan, s.trial_ends_at, s.current_period_ends_at
            FROM businesses b
            LEFT JOIN subscriptions s ON s.business_id = b.id
            WHERE b.id = :id
        SQL);
        $stmt->execute(['id' => $id->value]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateBusiness($row);
    }

    public function findBySubdomain(string $subdomain): ?BusinessDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT b.*, 
                   s.status AS sub_status, s.plan AS sub_plan, s.trial_ends_at, s.current_period_ends_at
            FROM businesses b
            LEFT JOIN subscriptions s ON s.business_id = b.id
            WHERE b.subdomain = :subdomain
        SQL);
        $stmt->execute(['subdomain' => strtolower($subdomain)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateBusiness($row);
    }

    public function findByCustomDomain(string $customDomain): ?BusinessDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT b.*, 
                   s.status AS sub_status, s.plan AS sub_plan, s.trial_ends_at, s.current_period_ends_at
            FROM businesses b
            LEFT JOIN subscriptions s ON s.business_id = b.id
            WHERE b.custom_domain = :domain AND b.custom_domain_status = 'ACTIVE'
        SQL);
        $stmt->execute(['domain' => strtolower($customDomain)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateBusiness($row);
    }

    public function findByHost(string $host): ?BusinessDTO
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host); // strip port

        $baseDomain = getenv('APP_DOMAIN') ?: 'tvojeaplikace.cz';

        // Check if it's a subdomain of app domain
        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
            if ($subdomain !== 'app' && $subdomain !== 'api') {
                return $this->findBySubdomain($subdomain);
            }
        }

        // Check custom domain
        return $this->findByCustomDomain($host);
    }

    public function create(
        BusinessProfileDTO $profile,
        string $email,
        string $phone,
        ?BusinessId $id = null,
    ): BusinessDTO {
        $businessId = $id ?? BusinessId::generate();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(<<<'SQL'
                INSERT INTO businesses (
                    id, email, phone, ico, company_name, street, city, zip,
                    archetype, main_trade_name, subdomain, custom_domain, custom_domain_status
                ) VALUES (
                    :id, :email, :phone, :ico, :company_name, :street, :city, :zip,
                    :archetype, :main_trade_name, :subdomain, :custom_domain, :custom_domain_status
                )
            SQL);

            $stmt->execute([
                'id' => $businessId->value,
                'email' => $email,
                'phone' => $phone,
                'ico' => $profile->ico,
                'company_name' => $profile->companyName,
                'street' => $profile->street,
                'city' => $profile->city,
                'zip' => $profile->zip,
                'archetype' => $profile->archetype->value,
                'main_trade_name' => $profile->mainTradeName,
                'subdomain' => $profile->subdomain,
                'custom_domain' => $profile->customDomain,
                'custom_domain_status' => $profile->customDomainStatus->value,
            ]);

            // Create trial subscription (14 days)
            $trialEnds = (new \DateTimeImmutable('+14 days'))->format(\DateTimeInterface::ATOM);
            $subStmt = $this->pdo->prepare(<<<'SQL'
                INSERT INTO subscriptions (business_id, status, trial_ends_at)
                VALUES (:business_id, 'TRIAL', :trial_ends_at)
            SQL);
            $subStmt->execute([
                'business_id' => $businessId->value,
                'trial_ends_at' => $trialEnds,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->findById($businessId) ?? throw new \RuntimeException('Failed to retrieve newly created business');
    }

    public function updateCustomDomain(BusinessId $id, string $domain, CustomDomainStatus $status): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            UPDATE businesses 
            SET custom_domain = :domain, custom_domain_status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        SQL);
        $stmt->execute([
            'id' => $id->value,
            'domain' => strtolower($domain),
            'status' => $status->value,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateBusiness(array $row): BusinessDTO
    {
        $profile = new BusinessProfileDTO(
            ico: (string) $row['ico'],
            companyName: (string) $row['company_name'],
            street: (string) $row['street'],
            city: (string) $row['city'],
            zip: (string) $row['zip'],
            archetype: Archetype::from((string) $row['archetype']),
            mainTradeName: (string) $row['main_trade_name'],
            subdomain: (string) $row['subdomain'],
            customDomain: $row['custom_domain'] !== null ? (string) $row['custom_domain'] : null,
            customDomainStatus: CustomDomainStatus::from((string) ($row['custom_domain_status'] ?? 'NONE')),
        );

        $subscription = new SubscriptionDTO(
            status: SubscriptionStatus::from((string) ($row['sub_status'] ?? 'TRIAL')),
            plan: !empty($row['sub_plan']) ? SubscriptionPlan::from((string) $row['sub_plan']) : null,
            trialEndsAt: new \DateTimeImmutable((string) $row['trial_ends_at']),
            currentPeriodEndsAt: !empty($row['current_period_ends_at']) ? new \DateTimeImmutable((string) $row['current_period_ends_at']) : null,
        );

        return new BusinessDTO(
            id: new BusinessId((string) $row['id']),
            email: (string) $row['email'],
            phone: (string) $row['phone'],
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            profile: $profile,
            subscription: $subscription,
        );
    }
}
