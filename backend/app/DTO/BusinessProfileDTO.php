<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Archetype;
use App\Domain\CustomDomainStatus;

final readonly class BusinessProfileDTO
{
    public function __construct(
        public string $ico,
        public string $companyName,
        public string $street,
        public string $city,
        public string $zip,
        public Archetype $archetype,
        public string $mainTradeName,
        public string $subdomain,
        public ?string $customDomain = null,
        public CustomDomainStatus $customDomainStatus = CustomDomainStatus::NONE,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ico' => $this->ico,
            'company_name' => $this->companyName,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->zip,
            'archetype' => $this->archetype->value,
            'main_trade_name' => $this->mainTradeName,
            'subdomain' => $this->subdomain,
            'custom_domain' => $this->customDomain,
            'custom_domain_status' => $this->customDomainStatus->value,
        ];
    }
}
