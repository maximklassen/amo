<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\HttpException;

/**
 * Prüft und bereinigt Eingaben. Regeln werden pro Feld als Liste angegeben:
 *
 *   $clean = (new Validator())->validated($request->body(), [
 *       'email'    => ['required', 'email', 'max:255', 'lowercase'],
 *       'password' => ['required', 'string', 'min:8', 'max_bytes:72', 'no_trim'],
 *       'zip'      => ['zip'],            // ohne "required": darf leer sein (wird dann null)
 *   ]);
 *
 * Regeln: required, string, email, min:n, max:n (Zeichen), max_bytes:n, zip, phone,
 *         in:a,b,c, lowercase (Umwandlung), no_trim (Leerzeichen nicht entfernen, für Passwörter)
 * Texte werden getrimmt, leere Texte werden zu null. Nicht aufgeführte Felder werden verworfen.
 */
final class Validator
{
    /**
     * @param array<string, mixed>        $data
     * @param array<string, list<string>> $rules
     * @return array<string, mixed> bereinigte Daten (nur Felder aus $rules)
     * @throws HttpException 400 mit Fehlermeldungen je Feld
     */
    public function validated(array $data, array $rules): array
    {
        [$clean, $errors] = $this->check($data, $rules);

        if ($errors !== []) {
            throw HttpException::validation($errors);
        }

        return $clean;
    }

    /**
     * @param array<string, mixed>        $data
     * @param array<string, list<string>> $rules
     * @return array{0: array<string, mixed>, 1: array<string, string>} [bereinigte Daten, Fehler je Feld]
     */
    public function check(array $data, array $rules): array
    {
        $clean = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            // Nur Texte, Zahlen und Booleans sind als Eingabe sinnvoll; Arrays/Objekte sind ein Fehler
            if (is_array($value) || is_object($value)) {
                $errors[$field] = 'Ungültiger Wert';
                continue;
            }

            if (is_string($value) && !in_array('no_trim', $fieldRules, true)) {
                $value = trim($value);
            }

            if ($value === '') {
                $value = null;
            }

            if ($value === null) {
                if (in_array('required', $fieldRules, true)) {
                    $errors[$field] = 'Pflichtfeld';
                }

                $clean[$field] = null;
                continue;
            }

            foreach ($fieldRules as $rule) {
                $error = $this->apply($rule, $value);

                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }

            if (in_array('lowercase', $fieldRules, true) && is_string($value)) {
                $value = mb_strtolower($value);
            }

            $clean[$field] = $value;
        }

        return [$clean, $errors];
    }

    /** @return string|null Fehlermeldung oder null, wenn der Wert die Regel erfüllt */
    private function apply(string $rule, mixed $value): ?string
    {
        [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
        $text = is_scalar($value) ? (string) $value : '';

        return match ($name) {
            'string'    => is_string($value) ? null : 'Ungültiger Wert',
            'email'     => filter_var($text, FILTER_VALIDATE_EMAIL) !== false ? null : 'Ungültige E-Mail-Adresse',
            'min'       => mb_strlen($text) >= (int) $argument ? null : sprintf('Mindestens %d Zeichen', (int) $argument),
            'max'       => mb_strlen($text) <= (int) $argument ? null : sprintf('Höchstens %d Zeichen', (int) $argument),
            'max_bytes' => strlen($text) <= (int) $argument ? null : 'Zu lang (bitte kürzer wählen)',
            'zip'       => preg_match('/^\d{5}$/', $text) === 1 ? null : 'Bitte 5 Ziffern angeben',
            'phone'     => preg_match('/^[0-9+()\/ \-]{5,30}$/', $text) === 1 ? null : 'Ungültige Telefonnummer',
            'in'        => in_array($text, explode(',', (string) $argument), true) ? null : 'Ungültiger Wert',
            default     => null, // required, lowercase, no_trim werden an anderer Stelle behandelt
        };
    }
}
