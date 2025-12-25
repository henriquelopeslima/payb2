<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidTransferValueException extends DomainException
{
    public const string MESSAGE = 'Invalid transfer value';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
