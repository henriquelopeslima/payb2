<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface TransferAuthorizationServiceInterface
{
    public function authorize(): void;
}
