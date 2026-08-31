<?php

declare(strict_types=1);

namespace App\Domain;

enum Mood: string
{
    case MODERN = 'MODERN';
    case TRADITIONAL = 'TRADITIONAL';
    case BOLD = 'BOLD';
    case ELEGANT = 'ELEGANT';
}
