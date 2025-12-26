<?php

declare(strict_types=1);

namespace App\Application\Port\Queue;

interface EventBusInterface
{
    public function dispatch(object $message): void;
}
