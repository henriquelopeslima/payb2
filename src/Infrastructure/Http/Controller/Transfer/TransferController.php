<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Transfer;

use App\Application\UseCase\Transfer\PerformTransferCommand;
use App\Application\UseCase\Transfer\PerformTransferInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TransferController
{
    public function __construct(private PerformTransferInterface $useCase) {}

    #[Route(path: '/transfer', name: 'transfer', methods: ['POST'])]
    public function transfer(#[MapRequestPayload] TransferInput $input): Response
    {
        $result = ($this->useCase)(
            new PerformTransferCommand(
                payerId: $input->payer,
                payeeId: $input->payee,
                value: $input->value,
            )
        );

        return new JsonResponse(
            data: ['transfer_id' => $result->transferId->toRfc4122()],
            status: Response::HTTP_CREATED
        );
    }
}
