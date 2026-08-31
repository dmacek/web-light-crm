<?php

declare(strict_types=1);

namespace App\Domain;

enum SubscriptionStatus: string
{
    case TRIAL = 'TRIAL';
    case ACTIVE = 'ACTIVE';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
