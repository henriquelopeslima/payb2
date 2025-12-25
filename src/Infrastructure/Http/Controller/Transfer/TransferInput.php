<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TransferInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $payer,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $payee,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public float $value,
    ) {}
}
