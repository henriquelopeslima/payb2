<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObject;

use InvalidArgumentException;
use RuntimeException;

final class Money
{
    private int $amountInCents;

    public function __construct(int $amountInCents)
    {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException(message: 'Amount cannot be negative.');
        }

        $this->amountInCents = $amountInCents;
    }

    public static function zero(): self
    {
        return new self(amountInCents: 0);
    }

    public static function fromFloat(float $value): self
    {
        return new self(amountInCents: (int) round(num: $value * 100));
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function add(self $other): self
    {
        return new self(amountInCents: $this->amountInCents + $other->amountInCents);
    }

    public function subtract(self $other): self
    {
        $result = $this->amountInCents - $other->amountInCents;

        if ($result < 0) {
            throw new RuntimeException(message: 'Resulting amount cannot be negative.');
        }

        return new self($result);
    }

    public function isGreaterOrEqual(self $other): bool
    {
        return $this->amountInCents >= $other->amountInCents;
    }
}
