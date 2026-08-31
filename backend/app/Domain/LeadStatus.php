<?php

declare(strict_types=1);

namespace App\Domain;

enum LeadStatus: string
{
    case NEW = 'NEW';
    case CALL_BACK = 'CALL_BACK';
    case RESOLVED = 'RESOLVED';
}
