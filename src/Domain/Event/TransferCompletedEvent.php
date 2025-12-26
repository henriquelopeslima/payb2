<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Symfony\Component\Uid\Uuid;

readonly class TransferCompletedEvent
{
    public function __construct(
        public Uuid $transferId,
    ) {}
}
