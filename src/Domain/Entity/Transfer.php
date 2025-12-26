<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\TransferStatus;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Exception\SelfTransferNotAllowedException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

class Transfer
{
    public function __construct(
        public ?Uuid $id,
        public readonly User $payer,
        public readonly User $payee,
        public readonly Money $amount,
        public TransferStatus $status,
        public readonly DateTimeImmutable $createdAt,
    ) {
        if ($payer->id->toRfc4122() === $payee->id->toRfc4122()) {
            throw new SelfTransferNotAllowedException();
        }

        $this->id = $id ?? Uuid::v7();
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

    public function markCompleted(): void
    {
        $this->status = TransferStatus::COMPLETED;
    }

    public function markFailed(): void
    {
        $this->status = TransferStatus::FAILED;
    }
}
