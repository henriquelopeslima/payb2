<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Wallet;
use Symfony\Component\Uid\Uuid;

interface WalletRepositoryInterface
{
    public function findByUserIdExclusiveLock(Uuid $userId): Wallet;

    public function save(Wallet $wallet): void;
}
