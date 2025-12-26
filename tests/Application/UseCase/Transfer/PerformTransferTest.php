<?php

declare(strict_types=1);

namespace App\Tests\Application\UseCase\Transfer;

use App\Application\Port\Persistence\TransactionManagerInterface;
use App\Application\Port\Queue\EventBusInterface;
use App\Application\UseCase\Transfer\PerformTransfer;
use App\Application\UseCase\Transfer\PerformTransferCommand;
use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\ValueObject\PasswordHash;
use App\Domain\Entity\Wallet;
use App\Domain\Event\TransferCompletedEvent;
use App\Domain\Exception\NotAllowedPayerException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Exception\SelfTransferNotAllowedException;
use App\Domain\Exception\TransferNotAuthorizedException;
use App\Domain\Repository\TransferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\WalletRepositoryInterface;
use App\Domain\Service\MoneyTransferrerService;
use App\Domain\Service\TransferAuthorizationServiceInterface;
use Closure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PerformTransferTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private WalletRepositoryInterface $walletRepo;
    private TransferRepositoryInterface $transferRepo;
    private TransferAuthorizationServiceInterface $authService;
    private TransactionManagerInterface $txManager;
    private MoneyTransferrerService $moneyTransferrer;
    private EventBusInterface $bus;

    protected function setUp(): void
    {
        $this->userRepo = $this->createStub(UserRepositoryInterface::class);
        $this->walletRepo = $this->createStub(WalletRepositoryInterface::class);
        $this->transferRepo = $this->createStub(TransferRepositoryInterface::class);
        $this->authService = $this->createStub(TransferAuthorizationServiceInterface::class);
        $this->txManager = $this->createStub(TransactionManagerInterface::class);
        $this->moneyTransferrer = $this->createStub(MoneyTransferrerService::class);
        $this->bus = $this->createStub(EventBusInterface::class);
    }

    private function createUseCase(): PerformTransfer
    {
        return new PerformTransfer(
            $this->userRepo,
            $this->walletRepo,
            $this->transferRepo,
            $this->authService,
            $this->txManager,
            $this->moneyTransferrer,
            $this->bus,
        );
    }

    private function makeCommonUser(Uuid $id): User
    {
        return new User(
            id: $id,
            fullName: 'John Doe',
            document: new Document('12345678900'),
            email: new Email('john@example.com'),
            passwordHash: PasswordHash::fromHash('hash'),
            type: UserType::COMMON,
        );
    }

    private function makeMerchantUser(Uuid $id): User
    {
        return new User(
            id: $id,
            fullName: 'ACME Shop',
            document: new Document('00987654321'),
            email: new Email('shop@example.com'),
            passwordHash: PasswordHash::fromHash('hash'),
            type: UserType::MERCHANT,
        );
    }

    private function makeWallet(User $user, Money $balance): Wallet
    {
        return new Wallet(
            id: Uuid::v7(),
            user: $user,
            balance: $balance,
        );
    }

    public function testSuccessfulTransferPersistsAndMovesMoney(): void
    {
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->bus = $this->createMock(EventBusInterface::class);

        $payerId = Uuid::v4();
        $payeeId = Uuid::v4();
        $command = new PerformTransferCommand($payerId->toRfc4122(), $payeeId->toRfc4122(), 100.00);

        $payer = $this->makeCommonUser($payerId);
        $payee = $this->makeMerchantUser($payeeId);

        $this->userRepo
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payer, $payee) {
                if ($id->equals($payerId)) {
                    return $payer;
                }
                if ($id->equals($payeeId)) {
                    return $payee;
                }

                return null;
            });

        $payerWallet = $this->makeWallet($payer, Money::fromFloat(150.00));
        $payeeWallet = $this->makeWallet($payee, Money::fromFloat(10.00));

        $this->walletRepo->expects($this->exactly(2))
            ->method('findByUserIdExclusiveLock')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payerWallet, $payeeWallet) {
                if ($id->equals($payerId)) {
                    return $payerWallet;
                }
                if ($id->equals($payeeId)) {
                    return $payeeWallet;
                }
                $this->fail('Unexpected user id requested for wallet.');
            });

        $this->authService->expects($this->once())->method('authorize');
        $this->transferRepo->expects($this->exactly(2))->method('save');
        $this->moneyTransferrer->expects($this->once())->method('transfer');
        $this->walletRepo->expects($this->exactly(2))->method('save');
        $this->txManager->expects($this->once())->method('transactional');

        $this->moneyTransferrer->expects($this->once())
            ->method('transfer')
            ->with(
                $payerWallet,
                $payeeWallet,
                $this->callback(fn (Money $m) => $m->amountInCents() === Money::fromFloat(100.00)->amountInCents())
            );

        $this->walletRepo->expects($this->exactly(2))->method('save');

        $this->txManager->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (Closure $fn): void {
                $fn();
            });

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($event) {
                    return $event instanceof TransferCompletedEvent
                        && $event->transferId instanceof Uuid;
                })
            );

        $output = $this->createUseCase()($command);

        $this->assertInstanceOf(Uuid::class, $output->transferId);
        $this->assertTrue(Uuid::isValid((string) $output->transferId));
    }

    public function testThrowsWhenPayerOrPayeeNotFound(): void
    {
        $this->userRepo = $this->createStub(UserRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);
        $this->bus = $this->createMock(EventBusInterface::class);

        $payerId = Uuid::v4();
        $payeeId = Uuid::v4();
        $command = new PerformTransferCommand($payerId->toRfc4122(), $payeeId->toRfc4122(), 10.0);

        $existingPayee = $this->makeMerchantUser($payeeId);
        $this->userRepo->method('findById')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $existingPayee) {
                if ($id->equals($payerId)) {
                    return null;
                }
                if ($id->equals($payeeId)) {
                    return $existingPayee;
                }

                return null;
            });

        $this->authService->expects($this->never())->method('authorize');
        $this->walletRepo->expects($this->never())->method('findByUserIdExclusiveLock');
        $this->moneyTransferrer->expects($this->never())->method('transfer');
        $this->walletRepo->expects($this->never())->method('save');
        $this->transferRepo->expects($this->never())->method('save');
        $this->txManager->expects($this->never())->method('transactional');

        $this->bus->expects($this->never())->method('dispatch');

        $this->expectException(ResourceNotFoundException::class);
        $this->createUseCase()($command);
    }

    public function testThrowsOnSelfTransfer(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->bus = $this->createMock(EventBusInterface::class);

        $id = Uuid::v4();
        $command = new PerformTransferCommand($id->toRfc4122(), $id->toRfc4122(), 10.0);

        $this->userRepo->expects($this->never())->method('findById');
        $this->authService->expects($this->never())->method('authorize');
        $this->walletRepo->expects($this->never())->method('findByUserIdExclusiveLock');
        $this->moneyTransferrer->expects($this->never())->method('transfer');
        $this->walletRepo->expects($this->never())->method('save');
        $this->transferRepo->expects($this->never())->method('save');
        $this->txManager->expects($this->never())->method('transactional');

        $this->bus->expects($this->never())->method('dispatch');

        $this->expectException(SelfTransferNotAllowedException::class);
        $this->createUseCase()($command);
    }

    public function testThrowsWhenPayerCannotSendMoney(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);
        $this->bus = $this->createMock(EventBusInterface::class);

        $payerId = Uuid::v4();
        $payeeId = Uuid::v4();
        $command = new PerformTransferCommand($payerId->toRfc4122(), $payeeId->toRfc4122(), 10.0);

        $payer = $this->makeMerchantUser($payerId);
        $payee = $this->makeCommonUser($payeeId);

        $this->userRepo
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payer, $payee) {
                if ($id->equals($payerId)) {
                    return $payer;
                }
                if ($id->equals($payeeId)) {
                    return $payee;
                }

                return null;
            });

        $this->authService->expects($this->never())->method('authorize');
        $this->walletRepo->expects($this->never())->method('findByUserIdExclusiveLock');
        $this->moneyTransferrer->expects($this->never())->method('transfer');
        $this->walletRepo->expects($this->never())->method('save');
        $this->transferRepo->expects($this->never())->method('save');
        $this->txManager->expects($this->never())->method('transactional');

        $this->bus->expects($this->never())->method('dispatch');

        $this->expectException(NotAllowedPayerException::class);
        $this->createUseCase()($command);
    }

    public function testAuthorizationFailureBubblesUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);
        $this->bus = $this->createMock(EventBusInterface::class);

        $payerId = Uuid::v4();
        $payeeId = Uuid::v4();
        $command = new PerformTransferCommand($payerId->toRfc4122(), $payeeId->toRfc4122(), 10.0);

        $payer = $this->makeCommonUser($payerId);
        $payee = $this->makeMerchantUser($payeeId);

        $this->userRepo
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payer, $payee) {
                if ($id->equals($payerId)) {
                    return $payer;
                }
                if ($id->equals($payeeId)) {
                    return $payee;
                }

                return null;
            });

        $this->authService->expects($this->once())->method('authorize')
            ->willThrowException(new TransferNotAuthorizedException());

        $this->walletRepo->expects($this->never())->method('findByUserIdExclusiveLock');
        $this->moneyTransferrer->expects($this->never())->method('transfer');
        $this->walletRepo->expects($this->never())->method('save');
        $this->transferRepo->expects($this->never())->method('save');
        $this->txManager->expects($this->never())->method('transactional');

        $this->bus->expects($this->never())->method('dispatch');

        $this->expectException(TransferNotAuthorizedException::class);
        $this->createUseCase()($command);
    }

    public function testAuthorizeHappensBeforeMoneyTransfer(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->authService = $this->createMock(TransferAuthorizationServiceInterface::class);
        $this->walletRepo = $this->createMock(WalletRepositoryInterface::class);
        $this->moneyTransferrer = $this->createMock(MoneyTransferrerService::class);
        $this->transferRepo = $this->createMock(TransferRepositoryInterface::class);
        $this->txManager = $this->createMock(TransactionManagerInterface::class);

        $payerId = Uuid::v4();
        $payeeId = Uuid::v4();
        $command = new PerformTransferCommand($payerId->toRfc4122(), $payeeId->toRfc4122(), 50.0);

        $payer = $this->makeCommonUser($payerId);
        $payee = $this->makeMerchantUser($payeeId);

        $this->userRepo
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payer, $payee) {
                if ($id->equals($payerId)) {
                    return $payer;
                }
                if ($id->equals($payeeId)) {
                    return $payee;
                }

                return null;
            });

        $payerWallet = $this->makeWallet($payer, Money::fromFloat(80.00));
        $payeeWallet = $this->makeWallet($payee, Money::fromFloat(40.00));

        $this->walletRepo
            ->expects($this->exactly(2))
            ->method('findByUserIdExclusiveLock')
            ->willReturnCallback(function (Uuid $id) use ($payerId, $payeeId, $payerWallet, $payeeWallet) {
                if ($id->equals($payerId)) {
                    return $payerWallet;
                }
                if ($id->equals($payeeId)) {
                    return $payeeWallet;
                }

                return null;
            });

        $authorized = false;
        $this->authService->expects($this->once())->method('authorize')
            ->willReturnCallback(function () use (&$authorized): void { $authorized = true; });
        $this->moneyTransferrer->expects($this->once())->method('transfer');
        $this->transferRepo->expects($this->atLeastOnce())->method('save');
        $this->txManager->expects($this->atLeastOnce())->method('transactional')
            ->willReturnCallback(fn (Closure $fn) => $fn());

        $this->createUseCase()($command);
        $this->assertTrue($authorized);
    }
}
