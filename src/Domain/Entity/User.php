<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\PasswordHash;
use Symfony\Component\Uid\Uuid;

final class User
{
    public function __construct(
        private Uuid $id,
        private readonly string $fullName,
        private readonly Document $document,
        private readonly Email $email,
        private readonly PasswordHash $passwordHash,
        private readonly UserType $type,
    ) {
        $this->id = Uuid::v7();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function document(): Document
    {
        return $this->document;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function type(): UserType
    {
        return $this->type;
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
