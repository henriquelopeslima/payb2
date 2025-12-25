<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Wallet;
use App\Domain\Repository\WalletRepositoryInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineWalletRepository implements WalletRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function findByUserIdExclusiveLock(Uuid $userId): Wallet
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('w')
            ->from(Wallet::class, 'w')
            ->where('w.user = :userId')
            ->setParameter('userId', $userId);

        $query = $qb->getQuery();

        $query->setLockMode(lockMode: LockMode::PESSIMISTIC_WRITE);

        return $query->getSingleResult();
    }

    public function save(Wallet $wallet): void
    {
        $this->entityManager->persist($wallet);
    }
}
