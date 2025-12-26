<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\PasswordHash;
use Symfony\Component\Uid\Uuid;

class User
{
    public function __construct(
        public ?Uuid $id,
        public readonly string $fullName,
        public readonly Document $document,
        public readonly Email $email,
        public readonly PasswordHash $passwordHash,
        public readonly UserType $type,
    ) {
        $this->id = $id ?? Uuid::v7();
    }

    public function isMerchant(): bool
    {
        return UserType::MERCHANT === $this->type;
    }

    public function isCommon(): bool
    {
        return UserType::COMMON === $this->type;
    }

    public function canSendMoney(): bool
    {
        return $this->isCommon();
    }
}
