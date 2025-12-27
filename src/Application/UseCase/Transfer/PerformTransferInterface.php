<?php

declare(strict_types=1);

namespace App\Application\UseCase\Transfer;

interface PerformTransferInterface
{
    public function __invoke(PerformTransferCommand $command): PerformTransferOutput;
}
