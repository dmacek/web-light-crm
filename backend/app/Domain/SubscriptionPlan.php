<?php

declare(strict_types=1);

namespace App\Domain;

enum SubscriptionPlan: string
{
    case MONTHLY = 'MONTHLY';
    case ANNUAL = 'ANNUAL';
}
