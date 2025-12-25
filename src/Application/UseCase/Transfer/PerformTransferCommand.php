<?php

declare(strict_types=1);

namespace App\Application\UseCase\Transfer;

final readonly class PerformTransferCommand
{
    public function __construct(
        public string $payerId,
        public string $payeeId,
        public float $value,
    ) {}
}
