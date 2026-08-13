<?php

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create the standard currency set — USD (default base), IDR and EUR — with
 * configurable per-currency rates keyed by code. Idempotent: existing rows
 * are never recreated, so it can be called repeatedly within one test.
 *
 * @param  array<string, float>  $rates
 */
function seedCurrencies(array $rates = []): void
{
    Currency::firstOrCreate(
        ['code' => 'usd'],
        ['name' => 'USD (US Dollar)', 'symbol' => '$', 'exchange_rate' => 1, 'decimal_places' => 2, 'is_enabled' => true, 'is_default' => true],
    );

    Currency::firstOrCreate(
        ['code' => 'idr'],
        ['name' => 'IDR (Rupiah)', 'symbol' => 'Rp', 'exchange_rate' => $rates['idr'] ?? 15000, 'decimal_places' => 0, 'is_enabled' => true, 'is_default' => false],
    );

    Currency::firstOrCreate(
        ['code' => 'eur'],
        ['name' => 'EUR (Euro)', 'symbol' => '€', 'exchange_rate' => $rates['eur'] ?? 0.9, 'decimal_places' => 2, 'is_enabled' => true, 'is_default' => false],
    );
}
