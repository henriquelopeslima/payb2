<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\Wallet;

class MoneyTransferrerService
{
    public function transfer(Wallet $from, Wallet $to, Money $amount): void
    {
        $from->debit($amount);
        $to->deposit($amount);
    }
}
