<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidAmount;
use App\Domain\ValueObjects\Amount;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    public function test_throws_invalid_amount_on_negative_subunit(): void
    {
        $this->expectException(InvalidAmount::class);
        Amount::fromSubunit(-1);
    }

    public function test_parses_eight_decimal_precision(): void
    {
        $this->assertSame(1, Amount::fromDecimal('0.00000001')->subunit());
    }

    public function test_rejects_more_than_eight_decimals(): void
    {
        $this->expectException(InvalidAmount::class);
        Amount::fromDecimal('0.123456789');
    }

    public function test_formats_always_pads_to_eight_decimals(): void
    {
        $this->assertSame('0.00000001', Amount::fromSubunit(1)->toDecimal());
    }
}