<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Enum\TransferStatus;
use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\Transfer;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\ValueObject\PasswordHash;
use App\Domain\Exception\SelfTransferNotAllowedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TransferTest extends TestCase
{
    private function makeUser(): User
    {
        return new User(
            id: Uuid::v4(),
            fullName: 'John',
            document: new Document('12345678901'),
            email: new Email('john@example.com'),
            passwordHash: PasswordHash::fromHash('hash'),
            type: UserType::COMMON
        );
    }

    public function testCreateNewSetsPendingStatus(): void
    {
        $payer = $this->makeUser();
        $payee = $this->makeUser();
        $transfer = Transfer::createNew($payer, $payee, new Money(1000));

        $this->assertSame(TransferStatus::PENDING, $transfer->status);
        $this->assertSame(1000, $transfer->amount->amountInCents());
        $this->assertNotNull($transfer->createdAt);
    }

    public function testSelfTransferThrows(): void
    {
        $payer = $this->makeUser();
        $payee = $payer;
        $this->expectException(SelfTransferNotAllowedException::class);
        Transfer::createNew($payer, $payee, new Money(100));
    }

    public function testStatusTransitions(): void
    {
        $payer = $this->makeUser();
        $payee = $this->makeUser();
        $transfer = Transfer::createNew($payer, $payee, new Money(500));

        $transfer->markCompleted();
        $this->assertSame(TransferStatus::COMPLETED, $transfer->status);

        $transfer->markFailed();
        $this->assertSame(TransferStatus::FAILED, $transfer->status);
    }
}
