<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Consumer;

use App\Application\Port\Service\NotificationServiceInterface;
use App\Domain\Event\TransferCompletedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TransferNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {}

    public function __invoke(TransferCompletedEvent $message): void
    {
        $this->notificationService->notify();
    }
}
