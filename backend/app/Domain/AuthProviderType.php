<?php

declare(strict_types=1);

namespace App\Domain;

enum AuthProviderType: string
{
    case SEZNAM = 'SEZNAM';
    case GOOGLE = 'GOOGLE';
    case APPLE = 'APPLE';
    case EMAIL_MAGIC_LINK = 'EMAIL_MAGIC_LINK';
}
