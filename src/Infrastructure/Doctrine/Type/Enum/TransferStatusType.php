<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type\Enum;

use App\Domain\Entity\Enum\TransferStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

final class TransferStatusType extends Type
{
    public const string NAME = 'transfer_status_enum';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?TransferStatus
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return TransferStatus::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof TransferStatus) {
            return $value->value;
        }

        throw new InvalidArgumentException('TransferStatusType expects instance of TransferStatus or null.');
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
