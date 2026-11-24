<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['ENVTEST_A'], $_ENV['ENVTEST_B']);
        putenv('ENVTEST_C');
    }

    public function testStringReturnsDefaultWhenMissing(): void
    {
        self::assertNull(Env::string('ENVTEST_MISSING'));
        self::assertSame('x', Env::string('ENVTEST_MISSING', 'x'));
    }

    public function testStringReadsEnvSuperglobalAndFallsBackToGetenv(): void
    {
        $_ENV['ENVTEST_A'] = 'from-env';
        putenv('ENVTEST_C=from-getenv');

        self::assertSame('from-env', Env::string('ENVTEST_A'));
        self::assertSame('from-getenv', Env::string('ENVTEST_C'));
    }

    public function testEmptyValueIsNotReplacedByDefault(): void
    {
        $_ENV['ENVTEST_A'] = '';

        self::assertSame('', Env::string('ENVTEST_A', 'default'));
    }

    public function testStringKeepsMagicWordsUntouched(): void
    {
        // Ein Passwort wie "null" oder "true" darf nicht in einen anderen Typ umgewandelt werden
        $_ENV['ENVTEST_A'] = 'null';
        self::assertSame('null', Env::string('ENVTEST_A'));

        $_ENV['ENVTEST_A'] = 'true';
        self::assertSame('true', Env::string('ENVTEST_A'));
    }

    public function testBoolParsing(): void
    {
        $cases = ['true' => true, '1' => true, 'on' => true, 'false' => false, '0' => false, 'off' => false];

        foreach ($cases as $raw => $expected) {
            $_ENV['ENVTEST_B'] = (string) $raw;

            self::assertSame($expected, Env::bool('ENVTEST_B', !$expected), 'Wert: ' . $raw);
        }
    }

    public function testBoolFallsBackToDefaultForMissingEmptyOrInvalid(): void
    {
        self::assertTrue(Env::bool('ENVTEST_B', true));
        self::assertFalse(Env::bool('ENVTEST_B', false));

        $_ENV['ENVTEST_B'] = '';
        self::assertTrue(Env::bool('ENVTEST_B', true));

        $_ENV['ENVTEST_B'] = 'maybe';
        self::assertTrue(Env::bool('ENVTEST_B', true));
        self::assertFalse(Env::bool('ENVTEST_B', false));
    }

    public function testInt(): void
    {
        $_ENV['ENVTEST_B'] = '3306';
        self::assertSame(3306, Env::int('ENVTEST_B', 1));

        $_ENV['ENVTEST_B'] = 'abc';
        self::assertSame(1, Env::int('ENVTEST_B', 1));

        unset($_ENV['ENVTEST_B']);
        self::assertSame(7, Env::int('ENVTEST_B', 7));
    }
}
