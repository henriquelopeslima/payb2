<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum TransferStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
}
