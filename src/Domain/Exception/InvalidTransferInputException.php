<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidTransferInputException extends DomainException
{
    public const string MESSAGE = 'Invalid transfer input';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
