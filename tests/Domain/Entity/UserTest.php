<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\PasswordHash;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function testUserRoleChecks(): void
    {
        $common = new User(Uuid::v7(), 'John Doe', new Document('12345678901'), new Email('john@example.com'), PasswordHash::fromHash('hash'), UserType::COMMON);
        $merchant = new User(Uuid::v7(), 'Acme Inc', new Document('12345678901234'), new Email('merchant@example.com'), PasswordHash::fromHash('hash'), UserType::MERCHANT);

        $this->assertTrue($common->isCommon());
        $this->assertFalse($common->isMerchant());
        $this->assertTrue($common->canSendMoney());

        $this->assertTrue($merchant->isMerchant());
        $this->assertFalse($merchant->isCommon());
        $this->assertFalse($merchant->canSendMoney());
    }
}
