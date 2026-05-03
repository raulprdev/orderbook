<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidMoney;
use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_throws_on_negative_micro_usd(): void
    {
        $this->expectException(InvalidMoney::class);
        Money::fromMicroUsd(-1);
    }

    public static function validUsdStrings(): array
    {
        return [
            'zero'                  => ['0',          0],
            'integer dollars'       => ['14',         14_000_000],
            'two decimals'          => ['14.25',      14_250_000],
            'six decimals exact'    => ['14.250001',  14_250_001],
            'large value'           => ['1000000',    1_000_000_000_000],
            'fractional cents only' => ['0.000001',   1],
        ];
    }

    public static function invalidUsdStrings(): array
    {
        return [
            'negative'             => ['-1.00'],
            'too many decimals'    => ['14.2500001'],
            'trailing dot'         => ['14.'],
            'leading dot'          => ['.25'],
            'multiple dots'        => ['14.2.3'],
            'scientific notation'  => ['1e5'],
            'letters'              => ['abc'],
            'empty string'         => [''],
            'leading whitespace'   => [' 14.25'],
            'trailing whitespace'  => ['14.25 '],
        ];
    }

    #[DataProvider('validUsdStrings')]
    public function test_parses_valid_usd_strings(string $input, int $expectedMicroUsd): void
    {
        $this->assertSame($expectedMicroUsd, Money::fromUsd($input)->microUsd());
    }

    #[DataProvider('invalidUsdStrings')]
    public function test_rejects_invalid_usd_strings(string $input): void
    {
        $this->expectException(InvalidMoney::class);
        Money::fromUsd($input);
    }

    public static function formattingCases(): array
    {
        return [
            'zero formats with two decimals'           => [0,            '0.00'],
            'whole dollars format with two decimals'   => [14_000_000,   '14.00'],
            'two-decimal value formats compact'        => [14_250_000,   '14.25'],
            'sub-cent precision shown when present'    => [14_250_001,   '14.250001'],
            'one micro shown with full precision'      => [1,            '0.000001'],
        ];
    }

    #[DataProvider('formattingCases')]
    public function test_formats_to_usd(int $microUsd, string $expected): void
    {
        $this->assertSame($expected, Money::fromMicroUsd($microUsd)->toUsd());
    }

    public function test_plus_returns_sum(): void
    {
        $a = Money::fromMicroUsd(10_000_000);
        $b = Money::fromMicroUsd(4_250_000);

        $this->assertSame(14_250_000, $a->plus($b)->microUsd());
    }

    public function test_minus_returns_difference(): void
    {
        $a = Money::fromMicroUsd(14_250_000);
        $b = Money::fromMicroUsd(4_250_000);

        $this->assertSame(10_000_000, $a->minus($b)->microUsd());
    }

    public function test_minus_throws_when_result_would_be_negative(): void
    {
        $this->expectException(InvalidMoney::class);
        Money::fromMicroUsd(100)->minus(Money::fromMicroUsd(101));
    }

    public function test_equals_compares_micro_usd(): void
    {
        $this->assertTrue(Money::fromMicroUsd(100)->equals(Money::fromMicroUsd(100)));
        $this->assertFalse(Money::fromMicroUsd(100)->equals(Money::fromMicroUsd(101)));
    }
}
