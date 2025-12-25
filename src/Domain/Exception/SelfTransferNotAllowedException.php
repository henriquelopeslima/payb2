<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class SelfTransferNotAllowedException extends DomainException
{
    public const string MESSAGE = 'Self transfers are not allowed';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
