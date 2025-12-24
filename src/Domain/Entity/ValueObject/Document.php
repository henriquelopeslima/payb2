<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObject;

use InvalidArgumentException;

final class Document
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = preg_replace('/\s+/', '', $value) ?? '';

        if ('' === $normalized) {
            throw new InvalidArgumentException('Document cannot be empty.');
        }

        if (11 !== strlen($normalized) && 14 !== strlen($normalized)) {
            throw new InvalidArgumentException(message: 'Document is not valid.');
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
