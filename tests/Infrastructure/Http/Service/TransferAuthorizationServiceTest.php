<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Http\Service;

use App\Domain\Exception\TransferNotAuthorizedException;
use App\Domain\Exception\TransferServiceUnavailableException;
use App\Infrastructure\Http\Service\TransferAuthorizationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class TransferAuthorizationServiceTest extends TestCase
{
    private function makeService(MockResponse $response): TransferAuthorizationService
    {
        $client = new MockHttpClient($response);

        return new TransferAuthorizationService($client, 'https://auth.example.test/authorize');
    }

    public function testAuthorizeSuccessOn200AuthorizedTrue(): void
    {
        $response = new MockResponse(json_encode(['data' => ['authorization' => true]]), ['http_code' => HttpResponse::HTTP_OK]);
        $service = $this->makeService($response);

        $service->authorize();
        $this->assertTrue(true);
    }

    public function testAuthorizeThrowsNotAuthorizedOn403(): void
    {
        $response = new MockResponse(json_encode(['data' => ['authorization' => false]]), ['http_code' => HttpResponse::HTTP_FORBIDDEN]);
        $service = $this->makeService($response);

        $this->expectException(TransferNotAuthorizedException::class);
        $service->authorize();
    }

    public function testAuthorizeThrowsServiceUnavailableOn500(): void
    {
        $response = new MockResponse('', ['http_code' => HttpResponse::HTTP_INTERNAL_SERVER_ERROR]);
        $service = $this->makeService($response);

        $this->expectException(TransferServiceUnavailableException::class);
        $service->authorize();
    }

    public function testAuthorizeThrowsServiceUnavailableOnMalformedPayload(): void
    {
        $response = new MockResponse('not-json', ['http_code' => HttpResponse::HTTP_OK]);
        $service = $this->makeService($response);

        $this->expectException(TransferServiceUnavailableException::class);
        $service->authorize();
    }

    public function testAuthorizeThrowsNotAuthorizedOnAuthorizationFalse(): void
    {
        $response = new MockResponse(json_encode(['data' => ['authorization' => false]]), ['http_code' => HttpResponse::HTTP_OK]);
        $service = $this->makeService($response);

        $this->expectException(TransferNotAuthorizedException::class);
        $service->authorize();
    }
}
