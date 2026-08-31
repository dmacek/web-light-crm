<?php

declare(strict_types=1);

namespace App\Domain;

enum CustomDomainStatus: string
{
    case NONE = 'NONE';
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case ERROR = 'ERROR';
}
