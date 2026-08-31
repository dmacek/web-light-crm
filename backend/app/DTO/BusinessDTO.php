<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\AuthProviderType;

final readonly class BusinessDTO
{
    /**
     * @param list<array{provider: AuthProviderType, provider_user_id: string, linked_at: \DateTimeImmutable}> $authProviders
     */
    public function __construct(
        public BusinessId $id,
        public string $email,
        public string $phone,
        public \DateTimeImmutable $createdAt,
        public BusinessProfileDTO $profile,
        public SubscriptionDTO $subscription,
        public array $authProviders = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'business_id' => $this->id->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'subscription' => $this->subscription->toArray(),
            'business_profile' => $this->profile->toArray(),
            'auth_providers' => array_map(static fn(array $item): array => [
                'provider' => $item['provider']->value,
                'provider_user_id' => $item['provider_user_id'],
                'linked_at' => $item['linked_at']->format(\DateTimeInterface::ATOM),
            ], $this->authProviders),
        ];
    }
}
