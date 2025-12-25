<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Transaction;

use App\Application\Port\Persistence\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function transactional(callable $callback)
    {
        return $this->entityManager->wrapInTransaction(func: $callback);
    }
}
