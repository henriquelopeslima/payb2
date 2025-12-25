<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Transfer;
use App\Domain\Repository\TransferRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTransferRepository implements TransferRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function findById(Uuid $id): ?Transfer
    {
        return $this->entityManager->find(Transfer::class, $id);
    }

    public function save(Transfer $transfer): void
    {
        $this->entityManager->persist($transfer);
    }
}
