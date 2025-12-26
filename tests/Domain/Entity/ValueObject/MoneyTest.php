<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity\ValueObject;

use App\Domain\Entity\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MoneyTest extends TestCase
{
    public function testCreateZeroAndAmountInCents(): void
    {
        $zero = Money::zero();
        $this->assertSame(0, $zero->amountInCents());

        $m = new Money(150);
        $this->assertSame(150, $m->amountInCents());
    }

    public function testFromFloatRoundsProperly(): void
    {
        $m = Money::fromFloat(12.34);
        $this->assertSame(1234, $m->amountInCents());
    }

    public function testCannotCreateNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-1);
    }

    public function testAddAndSubtract(): void
    {
        $a = new Money(500);
        $b = new Money(200);

        $this->assertSame(700, $a->add($b)->amountInCents());
        $this->assertSame(300, $a->subtract($b)->amountInCents());
    }

    public function testSubtractCannotGoNegative(): void
    {
        $a = new Money(100);
        $b = new Money(200);
        $this->expectException(RuntimeException::class);
        $a->subtract($b);
    }

    public function testComparisonIsGreaterOrEqual(): void
    {
        $a = new Money(200);
        $b = new Money(200);
        $c = new Money(100);

        $this->assertTrue($a->isGreaterOrEqual($b));
        $this->assertTrue($a->isGreaterOrEqual($c));
        $this->assertFalse($c->isGreaterOrEqual($a));
    }
}
