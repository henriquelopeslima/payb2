<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InsufficientBalanceException extends DomainException
{
    public const string MESSAGE = 'Insufficient balance to complete the transaction.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
