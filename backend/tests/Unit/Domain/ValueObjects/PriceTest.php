<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidPrice;
use App\Domain\ValueObjects\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function test_throws_invalid_price_on_negative_cent(): void
    {
        $this->expectException(InvalidPrice::class);
        Price::fromCent(-1);
    }

    public function test_throws_invalid_price_on_zero(): void
    {
        $this->expectException(InvalidPrice::class);
        Price::fromCent(0);
    }

    public function test_parses_two_decimal_precision(): void
    {
        $this->assertSame(9_500_025, Price::fromUsd('95000.25')->cent());
    }

    public function test_rejects_more_than_two_decimals(): void
    {
        $this->expectException(InvalidPrice::class);
        Price::fromUsd('95000.123');
    }

    public function test_formats_always_pads_to_two_decimals(): void
    {
        $this->assertSame('95000.00', Price::fromCent(9_500_000)->toUsd());
    }
}
