<?php

declare(strict_types=1);

namespace App\Application\UseCase\Transfer;

use App\Application\Port\Persistence\TransactionManagerInterface;
use App\Domain\Entity\Transfer;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Exception\NotAllowedPayerException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Exception\SelfTransferNotAllowedException;
use App\Domain\Repository\TransferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\WalletRepositoryInterface;
use App\Domain\Service\MoneyTransferrerService;
use App\Domain\Service\TransferAuthorizationServiceInterface;
use Symfony\Component\Uid\Uuid;

final readonly class PerformTransfer
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WalletRepositoryInterface $walletRepository,
        private TransferRepositoryInterface $transferRepository,
        private TransferAuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
        private MoneyTransferrerService $moneyTransferrer,
    ) {}

    public function __invoke(PerformTransferCommand $command): PerformTransferOutput
    {
        $payerId = Uuid::fromString($command->payerId);
        $payeeId = Uuid::fromString($command->payeeId);
        $amount = Money::fromFloat($command->value);

        $payer = $this->userRepository->findById($payerId);
        $payee = $this->userRepository->findById($payeeId);

        if (null === $payer || null === $payee) {
            throw new ResourceNotFoundException(message: 'Payer or payee not found.');
        }

        if ($payerId->equals($payeeId)) {
            throw new SelfTransferNotAllowedException();
        }

        if (!$payer->canSendMoney()) {
            throw new NotAllowedPayerException();
        }

        $transfer = Transfer::createNew($payer, $payee, $amount);

        $this->authorizationService->authorize();

        $this->transactionManager->transactional(function () use ($payerId, $payeeId, $amount, $transfer): void {
            $payerWallet = $this->walletRepository->findByUserIdExclusiveLock($payerId);
            $payeeWallet = $this->walletRepository->findByUserIdExclusiveLock($payeeId);

            $this->transferRepository->save($transfer);

            $this->moneyTransferrer->transfer($payerWallet, $payeeWallet, $amount);

            $transfer->markCompleted();

            $this->walletRepository->save($payerWallet);
            $this->walletRepository->save($payeeWallet);
            $this->transferRepository->save($transfer);
        });

        return new PerformTransferOutput($transfer->id());
    }
}
