<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use App\Application\Port\Queue\EventBusInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyMessageBusAdapter implements EventBusInterface
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(object $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
