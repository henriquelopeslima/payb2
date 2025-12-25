<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class NotAllowedPayerException extends DomainException
{
    public const string MESSAGE = 'Not allowed payer';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
