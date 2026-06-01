<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
}
