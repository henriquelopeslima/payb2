<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObject;

use InvalidArgumentException;

final class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if ('' === $normalized || !filter_var(value: $normalized, filter: FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(message: 'Invalid email.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
