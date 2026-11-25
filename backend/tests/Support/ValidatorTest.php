<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\HttpException;
use App\Support\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator();
    }

    public function testValidDataIsReturnedCleaned(): void
    {
        $clean = $this->v->validated(
            ['email' => '  Anna@Example.DE ', 'first_name' => ' Anna ', 'evil' => 'x'],
            ['email' => ['required', 'email', 'lowercase'], 'first_name' => ['required', 'string']],
        );

        self::assertSame(['email' => 'anna@example.de', 'first_name' => 'Anna'], $clean); // "evil" verworfen
    }

    public function testRequiredFieldMissingOrEmpty(): void
    {
        [, $errors] = $this->v->check(['a' => '', 'b' => '   '], ['a' => ['required'], 'b' => ['required'], 'c' => ['required']]);

        self::assertSame(['a' => 'Pflichtfeld', 'b' => 'Pflichtfeld', 'c' => 'Pflichtfeld'], $errors);
    }

    public function testOptionalEmptyBecomesNull(): void
    {
        [$clean, $errors] = $this->v->check(['phone' => ' '], ['phone' => ['phone'], 'zip' => ['zip']]);

        self::assertSame([], $errors);
        self::assertSame(['phone' => null, 'zip' => null], $clean);
    }

    public function testEmailRule(): void
    {
        self::assertSame([], $this->v->check(['e' => 'a@b.de'], ['e' => ['email']])[1]);
        self::assertSame(['e' => 'Ungültige E-Mail-Adresse'], $this->v->check(['e' => 'kein-mail'], ['e' => ['email']])[1]);
    }

    public function testMinAndMaxCountCharactersNotBytes(): void
    {
        self::assertSame([], $this->v->check(['n' => 'äöü'], ['n' => ['min:3', 'max:3']])[1]);
        self::assertSame(['n' => 'Mindestens 4 Zeichen'], $this->v->check(['n' => 'äöü'], ['n' => ['min:4']])[1]);
        self::assertSame(['n' => 'Höchstens 2 Zeichen'], $this->v->check(['n' => 'äöü'], ['n' => ['max:2']])[1]);
    }

    public function testMaxBytesForBcryptLimit(): void
    {
        $ok = str_repeat('a', 72);
        $tooLong = str_repeat('a', 73);
        $umlauts = str_repeat('ä', 37); // 74 Bytes, aber nur 37 Zeichen

        self::assertSame([], $this->v->check(['p' => $ok], ['p' => ['max_bytes:72']])[1]);
        self::assertArrayHasKey('p', $this->v->check(['p' => $tooLong], ['p' => ['max_bytes:72']])[1]);
        self::assertArrayHasKey('p', $this->v->check(['p' => $umlauts], ['p' => ['max_bytes:72']])[1]);
    }

    public function testNoTrimKeepsSpacesInPasswords(): void
    {
        [$clean] = $this->v->check(['p' => '  geheim  '], ['p' => ['required', 'no_trim']]);
        self::assertSame('  geheim  ', $clean['p']);

        [$clean] = $this->v->check(['p' => '  geheim  '], ['p' => ['required']]);
        self::assertSame('geheim', $clean['p']);
    }

    public function testZipPhoneAndInRules(): void
    {
        self::assertSame([], $this->v->check(['z' => '29664', 't' => '+49 (5161) 12-34', 'r' => 'a'], ['z' => ['zip'], 't' => ['phone'], 'r' => ['in:a,b']])[1]);

        $errors = $this->v->check(['z' => '2966', 't' => 'abc', 'r' => 'c'], ['z' => ['zip'], 't' => ['phone'], 'r' => ['in:a,b']])[1];
        self::assertSame(['z', 't', 'r'], array_keys($errors));
    }

    public function testArraysAndObjectsAreRejected(): void
    {
        $errors = $this->v->check(['a' => ['x'], 'b' => new \stdClass()], ['a' => ['required'], 'b' => ['string']])[1];

        self::assertSame(['a' => 'Ungültiger Wert', 'b' => 'Ungültiger Wert'], $errors);
    }

    public function testStringRuleRejectsNumbers(): void
    {
        self::assertSame(['n' => 'Ungültiger Wert'], $this->v->check(['n' => 123], ['n' => ['string']])[1]);
    }

    public function testFirstFailingRuleWinsPerField(): void
    {
        $errors = $this->v->check(['e' => 'x'], ['e' => ['email', 'min:5']])[1];

        self::assertSame(['e' => 'Ungültige E-Mail-Adresse'], $errors);
    }

    public function testLowercaseHandlesUmlauts(): void
    {
        self::assertSame('ärger@x.de', $this->v->check(['e' => 'ÄRGER@X.DE'], ['e' => ['lowercase']])[0]['e']);
    }

    public function testValidatedThrowsHttp400WithFieldErrors(): void
    {
        try {
            $this->v->validated(['email' => 'x'], ['email' => ['email'], 'name' => ['required']]);
            self::fail('Ausnahme erwartet');
        } catch (HttpException $e) {
            self::assertSame(400, $e->getStatus());
            self::assertSame('validation_failed', $e->getErrorCode());
            self::assertSame(['email' => 'Ungültige E-Mail-Adresse', 'name' => 'Pflichtfeld'], $e->getErrors());
        }
    }
}
