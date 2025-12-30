<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Http\Controller;

use App\DataFixtures\UserFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TransferControllerTest extends WebTestCase
{
    private function mockExternalServices(ContainerInterface $container, array $responses): void
    {
        $mockClient = new MockHttpClient($responses);
        $container->set(HttpClientInterface::class, $mockClient);
    }

    public function testPerformTransferSuccess(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $responses = [
            new MockResponse(
                '{"data":{"authorization":true}}',
                ['http_code' => Response::HTTP_OK, 'response_headers' => ['content-type' => 'application/json']]
            ),
            new MockResponse('', ['http_code' => Response::HTTP_NO_CONTENT]),
        ];

        $this->mockExternalServices($container, $responses);

        $payer = UserFixtures::USER_ID_1;
        $payee = UserFixtures::USER_ID_2;

        $client->request(Request::METHOD_POST, '/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'payer' => $payer,
            'payee' => $payee,
            'value' => 10.0,
        ], JSON_THROW_ON_ERROR));

        $response = $client->getResponse();
        $this->assertSame(expected: Response::HTTP_CREATED, actual: $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $payload = json_decode($content, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('transfer_id', $payload);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $payload['transfer_id']);
    }

    #[DataProvider('provideInvalidTransfers')]
    public function testPerformTransferNegative(mixed $payer, mixed $payee, mixed $value, int $expectedStatus, string $expectedMessage): void
    {
        $client = self::createClient();

        $this->mockExternalServices(self::getContainer(), [
            new MockResponse('{"data":{"authorization":true}}', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $client->request(Request::METHOD_POST, '/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'payer' => $payer,
            'payee' => $payee,
            'value' => $value,
        ], JSON_THROW_ON_ERROR));

        $response = $client->getResponse();
        $this->assertSame($expectedStatus, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotSame('', $content);
        $payload = json_decode($content, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertSame($expectedMessage, $payload['message']);
    }

    public function testPerformTransferMissingFields(): void
    {
        $client = self::createClient();
        $this->mockExternalServices(self::getContainer(), [
            new MockResponse('{"data":{"authorization":true}}', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $client->request(Request::METHOD_POST, '/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'payer' => UserFixtures::USER_ID_1,
        ], JSON_THROW_ON_ERROR));

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('message', $payload);
    }

    public function testPerformTransferMalformedJson(): void
    {
        $client = self::createClient();
        $this->mockExternalServices(self::getContainer(), [
            new MockResponse('{"data":{"authorization":true}}', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $client->request(Request::METHOD_POST, '/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], '{"payer": "x"');

        $response = $client->getResponse();
        $this->assertTrue(in_array($response->getStatusCode(), [Response::HTTP_BAD_REQUEST, Response::HTTP_UNPROCESSABLE_ENTITY], true));
        $content = (string) $response->getContent();
        if ('' !== $content) {
            $payload = json_decode($content, true);
            if (is_array($payload)) {
                $this->assertArrayHasKey('message', $payload);
            }
        }
    }

    public static function provideInvalidTransfers(): iterable
    {
        $common1 = UserFixtures::USER_ID_1;
        $common2 = UserFixtures::USER_ID_2;
        $merchant = UserFixtures::USER_ID_8;

        return [
            'self transfer not allowed' => [$common1, $common1, 5.0, Response::HTTP_UNPROCESSABLE_ENTITY, 'Self transfers are not allowed'],
            'payer not allowed (merchant)' => [$merchant, $common2, 5.0, Response::HTTP_FORBIDDEN, 'Not allowed payer'],
            'resource not found (unknown payer)' => ['019b5e93-ffff-7000-8000-ffffffffffff', $common2, 5.0, Response::HTTP_NOT_FOUND, 'Payer or payee not found.'],
            'invalid value (negative)' => [$common1, $common2, -10.0, Response::HTTP_BAD_REQUEST, 'This value should be greater than 0.'],
            'value not valid type' => [$common1, $common2, '10.0', Response::HTTP_BAD_REQUEST, 'This value should be of type float.'],
            'invalid identifier' => [$common1, 1, 10.0, Response::HTTP_BAD_REQUEST, 'This value should be of type string.'],
        ];
    }
}
