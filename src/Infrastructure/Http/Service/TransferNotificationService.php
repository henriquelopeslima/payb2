<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Service;

use App\Application\Exception\NotificationServiceUnavailableException;
use App\Application\Port\Service\NotificationServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class TransferNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'TRANSFER_NOTIFICATION_URL')]
        private string $notificationUrl,
    ) {}

    public function notify(): void
    {
        try {
            $response = $this->httpClient->request(Request::METHOD_POST, $this->notificationUrl, ['timeout' => 3.0]);

            $status = $response->getStatusCode();

            if (Response::HTTP_NO_CONTENT !== $status) {
                throw new NotificationServiceUnavailableException();
            }
        } catch (TransportExceptionInterface) {
            throw new NotificationServiceUnavailableException();
        }
    }
}
