<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_cdf_arithmetic_uses_exact_minor_units(): void
    {
        $this->assertSame(30, Money::minor('0.10') + Money::minor('0.20'));
        $this->assertSame('5001.00', Money::decimal(Money::minor('2500.50') * 2));
        $this->assertSame('0.00', Money::decimal(0));
    }
}
