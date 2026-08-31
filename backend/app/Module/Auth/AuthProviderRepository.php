<?php

declare(strict_types=1);

namespace App\Module\Auth;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\Domain\AuthProviderType;

final class AuthProviderRepository
{
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
    }

    /**
     * @return array{business_id: string, provider: string, provider_user_id: string}|null
     */
    public function findByProvider(AuthProviderType $provider, string $providerUserId): ?array
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT business_id, provider, provider_user_id, linked_at
            FROM auth_providers
            WHERE provider = :provider AND provider_user_id = :user_id
        SQL);
        $stmt->execute([
            'provider' => $provider->value,
            'user_id' => $providerUserId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function link(BusinessId $businessId, AuthProviderType $provider, string $providerUserId): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO auth_providers (business_id, provider, provider_user_id)
            VALUES (:business_id, :provider, :user_id)
            ON CONFLICT (provider, provider_user_id) DO UPDATE
            SET business_id = EXCLUDED.business_id
        SQL);
        $stmt->execute([
            'business_id' => $businessId->value,
            'provider' => $provider->value,
            'user_id' => $providerUserId,
        ]);
    }

    /**
     * @return list<array{provider: string, provider_user_id: string, linked_at: string}>
     */
    public function findByBusinessId(BusinessId $businessId): array
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT provider, provider_user_id, linked_at
            FROM auth_providers
            WHERE business_id = :business_id
            ORDER BY linked_at ASC
        SQL);
        $stmt->execute(['business_id' => $businessId->value]);
        return $stmt->fetchAll() ?: [];
    }
}
