<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TransferServiceUnavailableException extends DomainException
{
    public const string MESSAGE = 'Transfer service unavailable.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
