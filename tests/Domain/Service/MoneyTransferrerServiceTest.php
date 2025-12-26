<?php

declare(strict_types=1);

namespace App\Tests\Domain\Service;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\ValueObject\PasswordHash;
use App\Domain\Entity\Wallet;
use App\Domain\Exception\InsufficientBalanceException;
use App\Domain\Service\MoneyTransferrerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MoneyTransferrerServiceTest extends TestCase
{
    private function makeUser(): User
    {
        return new User(
            Uuid::v7(),
            'John Doe',
            new Document('12345678901'),
            new Email('john@example.com'),
            PasswordHash::fromHash('hash'),
            UserType::COMMON
        );
    }

    public function testTransferDebitsAndDeposits(): void
    {
        $payer = $this->makeUser();
        $payee = $this->makeUser();

        $payerWallet = new Wallet(Uuid::v7(), $payer, new Money(1000));
        $payeeWallet = new Wallet(Uuid::v7(), $payee, Money::zero());

        $service = new MoneyTransferrerService();
        $service->transfer($payerWallet, $payeeWallet, new Money(400));

        $this->assertSame(600, $payerWallet->balance->amountInCents());
        $this->assertSame(400, $payeeWallet->balance->amountInCents());
    }

    public function testTransferThrowsInsufficientBalanceException(): void
    {
        $payer = $this->makeUser();
        $payee = $this->makeUser();

        $payerWallet = new Wallet(Uuid::v7(), $payer, new Money(1000));
        $payeeWallet = new Wallet(Uuid::v7(), $payee, Money::zero());

        $this->expectException(InsufficientBalanceException::class);

        $service = new MoneyTransferrerService();
        $service->transfer($payerWallet, $payeeWallet, new Money(1100));
    }
}
