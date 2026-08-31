<?php

declare(strict_types=1);

namespace App\Module\Public;

use App\Bootstrap;
use App\DTO\LeadDTO;
use App\Module\Crm\LeadRepository;
use App\Module\Domain\BusinessRepository;

final class LeadIntakeService
{
    private BusinessRepository $businessRepository;
    private LeadRepository $leadRepository;
    private \Predis\Client $redis;

    public function __construct(
        ?BusinessRepository $businessRepository = null,
        ?LeadRepository $leadRepository = null,
        ?\Predis\Client $redis = null,
    ) {
        $this->businessRepository = $businessRepository ?? new BusinessRepository();
        $this->leadRepository = $leadRepository ?? new LeadRepository();
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    public function submitLead(
        string $hostOrSubdomain,
        string $senderName,
        string $senderPhone,
        ?string $senderEmail,
        string $message,
        string $clientIp = '127.0.0.1',
    ): LeadDTO {
        // 1. Rate-limiting check: max 5 requests per 60 seconds per IP
        $rateLimitKey = "ratelimit:leads:{$clientIp}";
        try {
            $count = (int) $this->redis->incr($rateLimitKey);
            if ($count === 1) {
                $this->redis->expire($rateLimitKey, 60);
            }
            if ($count > 5) {
                throw new \OverflowException('Too many lead inquiries from this IP. Please wait a moment.');
            }
        } catch (\OverflowException $e) {
            throw $e;
        } catch (\Throwable) {
            // Ignore Redis errors to avoid blocking leads
        }

        // 2. Find business by host or subdomain
        $business = $this->businessRepository->findByHost($hostOrSubdomain);
        if ($business === null) {
            $business = $this->businessRepository->findBySubdomain($hostOrSubdomain);
        }

        if ($business === null) {
            throw new \InvalidArgumentException("Business website '{$hostOrSubdomain}' not found");
        }

        // 3. Validation
        $name = trim($senderName);
        if ($name === '') {
            throw new \InvalidArgumentException('Jméno a příjmení je povinné');
        }

        $phone = trim($senderPhone);
        if ($phone === '') {
            throw new \InvalidArgumentException('Telefonní číslo je povinné');
        }

        $msg = trim($message);
        if ($msg === '') {
            throw new \InvalidArgumentException('Zpráva nebo popis poptávky je povinný');
        }

        $email = $senderEmail ? trim($senderEmail) : null;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        // 4. Create Lead in CRM
        return $this->leadRepository->create(
            businessId: $business->id,
            senderName: $name,
            senderPhone: $phone,
            senderEmail: $email,
            message: $msg,
        );
    }
}
