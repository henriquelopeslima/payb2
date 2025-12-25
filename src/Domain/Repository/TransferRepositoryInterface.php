<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Transfer;
use Symfony\Component\Uid\Uuid;

interface TransferRepositoryInterface
{
    public function findById(Uuid $id): ?Transfer;

    public function save(Transfer $transfer): void;
}
