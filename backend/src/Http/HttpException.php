<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;
use Throwable;

/**
 * Ausnahme mit HTTP-Status. Der Kernel macht daraus eine JSON-Antwort:
 * {"error": "Nicht gefunden", "code": "not_found"}
 *
 * "error" ist der Text für den Benutzer (so steht es im API-Vertrag), "code" ist ein
 * optionales, maschinenlesbares Zusatzfeld für das React-Frontend.
 */
class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $headers zusätzliche Antwort-Header
     * @param array<string, string> $errors  Fehlermeldungen je Eingabefeld (bei Validierungsfehlern)
     */
    public function __construct(
        private readonly int $status,
        private readonly string $errorCode,
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $status, $previous);
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function badRequest(string $errorCode = 'bad_request', string $message = 'Ungültige Anfrage'): self
    {
        return new self(400, $errorCode, $message);
    }

    public static function unauthorized(string $message = 'Anmeldung erforderlich'): self
    {
        return new self(401, 'unauthorized', $message);
    }

    public static function forbidden(string $message = 'Zugriff verweigert'): self
    {
        return new self(403, 'forbidden', $message);
    }

    public static function notFound(string $message = 'Nicht gefunden'): self
    {
        return new self(404, 'not_found', $message);
    }

    public static function conflict(string $errorCode = 'conflict', string $message = 'Konflikt mit dem aktuellen Zustand'): self
    {
        return new self(409, $errorCode, $message);
    }

    /**
     * Ungültige Eingaben: laut API-Vertrag Status 400, mit einer Meldung je Feld.
     *
     * @param array<string, string> $errors Feldname => Fehlermeldung
     */
    public static function validation(array $errors, string $message = 'Die Eingaben sind ungültig'): self
    {
        return new self(400, 'validation_failed', $message, [], null, $errors);
    }

    /** @param int $retryAfter Sekunden, bis ein neuer Versuch sinnvoll ist */
    public static function tooManyRequests(int $retryAfter, string $message = 'Zu viele Versuche, bitte später erneut versuchen'): self
    {
        return new self(429, 'too_many_requests', $message, ['Retry-After' => (string) max(1, $retryAfter)]);
    }

    /** @param list<string> $allowed erlaubte Methoden für diese URL */
    public static function methodNotAllowed(array $allowed): self
    {
        return new self(405, 'method_not_allowed', 'Methode nicht erlaubt', ['Allow' => implode(', ', $allowed)]);
    }
}
