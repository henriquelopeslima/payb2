<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TransferNotAuthorizedException extends DomainException
{
    public const string MESSAGE = 'Transfer not authorized';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
