<?php

declare(strict_types=1);

namespace App\Application\Port\Service;

interface NotificationServiceInterface
{
    public function notify(): void;
}
