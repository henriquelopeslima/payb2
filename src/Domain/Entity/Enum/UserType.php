<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum UserType: string
{
    case COMMON = 'COMMON';
    case MERCHANT = 'MERCHANT';
}
