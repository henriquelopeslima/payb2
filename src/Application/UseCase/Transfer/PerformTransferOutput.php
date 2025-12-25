<?php

declare(strict_types=1);

namespace App\Application\UseCase\Transfer;

use Symfony\Component\Uid\Uuid;

final readonly class PerformTransferOutput
{
    public function __construct(
        public Uuid $transferId,
    ) {}
}
