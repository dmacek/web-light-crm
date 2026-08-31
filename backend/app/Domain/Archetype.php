<?php

declare(strict_types=1);

namespace App\Domain;

enum Archetype: string
{
    case PROVOZOVNA = 'PROVOZOVNA';
    case VYJEZDOVE_REMESLO = 'VYJEZDOVE_REMESLO';
    case ZAKAZKOVA_VYROBA = 'ZAKAZKOVA_VYROBA';
    case OSTATNI = 'OSTATNI';
}
