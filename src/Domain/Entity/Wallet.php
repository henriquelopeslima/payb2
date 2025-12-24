<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ValueObject\Money;
use App\Domain\Exception\InsufficientBalanceException;
use Symfony\Component\Uid\Uuid;

final class Wallet
{
    public function __construct(
        private Uuid $id,
        private readonly User $user,
        private Money $balance,
    ) {
        $this->id = Uuid::v7();
    }

    public static function createEmpty(Uuid $id, User $user): self
    {
        return new self($id, $user, Money::zero());
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function canDebit(Money $amount): bool
    {
        return $this->balance->isGreaterOrEqual($amount);
    }

    public function debit(Money $amount): void
    {
        if (!$this->canDebit($amount)) {
            throw new InsufficientBalanceException();
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }
}
