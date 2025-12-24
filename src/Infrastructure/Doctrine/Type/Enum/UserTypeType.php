<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type\Enum;

use App\Domain\Entity\Enum\UserType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

final class UserTypeType extends Type
{
    public const string NAME = 'user_type_enum';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserType
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return UserType::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof UserType) {
            return $value->value;
        }

        throw new InvalidArgumentException(message: 'UserTypeType expects instance of UserType or null.');
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
