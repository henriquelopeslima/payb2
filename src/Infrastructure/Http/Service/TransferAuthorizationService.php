<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Service;

use App\Domain\Exception\TransferNotAuthorizedException;
use App\Domain\Exception\TransferServiceUnavailableException;
use App\Domain\Service\TransferAuthorizationServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class TransferAuthorizationService implements TransferAuthorizationServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'TRANSFER_AUTHORIZATION_URL')]
        private string $authorizationUrl,
    ) {}

    public function authorize(): void
    {
        try {
            $response = $this->httpClient->request(Request::METHOD_GET, $this->authorizationUrl, ['timeout' => 3.0]);

            $status = $response->getStatusCode();

            if (Response::HTTP_FORBIDDEN === $status) {
                throw new TransferNotAuthorizedException();
            }

            if (Response::HTTP_OK !== $status) {
                throw new TransferServiceUnavailableException();
            }

            $data = $response->toArray(false);
            $authorized = $data['data']['authorization'] ?? false;

            if (true !== $authorized) {
                throw new TransferNotAuthorizedException();
            }
        } catch (ClientExceptionInterface) {
            throw new TransferNotAuthorizedException();
        } catch (DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface) {
            throw new TransferServiceUnavailableException();
        }
    }
}
