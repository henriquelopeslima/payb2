<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\TransferStatus;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Exception\SelfTransferNotAllowedException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class Transfer
{
    public function __construct(
        private Uuid $id,
        private readonly User $payer,
        private readonly User $payee,
        private readonly Money $amount,
        private TransferStatus $status,
        private readonly DateTimeImmutable $createdAt,
    ) {
        if ($payer->id()->toRfc4122() === $payee->id()->toRfc4122()) {
            throw new SelfTransferNotAllowedException();
        }

        $this->id = Uuid::v7();
    }

    public static function createNew(User $payer, User $payee, Money $amount): self
    {
        return new self(
            id: Uuid::v7(),
            payer: $payer,
            payee: $payee,
            amount: $amount,
            status: TransferStatus::PENDING,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function payer(): User
    {
        return $this->payer;
    }

    public function payee(): User
    {
        return $this->payee;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): TransferStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markCompleted(): void
    {
        $this->status = TransferStatus::COMPLETED;
    }

    public function markFailed(): void
    {
        $this->status = TransferStatus::FAILED;
    }
}
