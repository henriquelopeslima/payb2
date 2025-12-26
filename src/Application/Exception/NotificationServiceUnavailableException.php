<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class NotificationServiceUnavailableException extends ApplicationException
{
    public const string MESSAGE = 'Notification service unavailable.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct(message: $message);
    }
}
