<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Side;
use PHPUnit\Framework\TestCase;

final class SideTest extends TestCase
{
    public function test_buy_opposite_is_sell(): void
    {
        $this->assertSame(Side::Sell, Side::Buy->opposite());
    }

    public function test_sell_opposite_is_buy(): void
    {
        $this->assertSame(Side::Buy, Side::Sell->opposite());
    }
}
