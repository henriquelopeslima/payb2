<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\ValueObject\PasswordHash;
use App\Domain\Entity\Wallet;
use App\Domain\Exception\InsufficientBalanceException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WalletTest extends TestCase
{
    private function makeUser(): User
    {
        return new User(
            Uuid::v7(),
            'John Doe',
            new Document('12345678901'),
            new Email('john@example.com'),
            PasswordHash::fromHash('hash'),
            UserType::COMMON,
        );
    }

    public function testCreateEmpty(): void
    {
        $user = $this->makeUser();
        $wallet = Wallet::createEmpty(Uuid::v7(), $user);

        $this->assertSame(0, $wallet->balance->amountInCents());
    }

    public function testDebitAndDeposit(): void
    {
        $user = $this->makeUser();
        $wallet = new Wallet(Uuid::v7(), $user, new Money(1000));

        $this->assertTrue($wallet->canDebit(new Money(500)));
        $wallet->debit(new Money(500));
        $this->assertSame(500, $wallet->balance->amountInCents());

        $wallet->deposit(new Money(250));
        $this->assertSame(750, $wallet->balance->amountInCents());
    }

    public function testDebitInsufficientBalanceThrows(): void
    {
        $user = $this->makeUser();
        $wallet = new Wallet(Uuid::v7(), $user, new Money(300));
        $this->expectException(InsufficientBalanceException::class);
        $wallet->debit(new Money(500));
    }
}
