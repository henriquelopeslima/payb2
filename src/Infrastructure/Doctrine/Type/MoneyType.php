<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Entity\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

final class MoneyType extends Type
{
    public const string NAME = 'money_vo';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return new Money((int) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new InvalidArgumentException(message: 'MoneyType expects instance of Money or null.');
        }

        return $value->amountInCents();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
