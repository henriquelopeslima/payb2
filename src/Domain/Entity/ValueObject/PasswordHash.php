<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObject;

use InvalidArgumentException;

final class PasswordHash
{
    private string $value;

    private function __construct(string $hash)
    {
        if ('' === $hash) {
            throw new InvalidArgumentException(message: 'Password hash cannot be empty.');
        }

        $this->value = $hash;
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
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
