<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;
use Throwable;

/**
 * Ausnahme mit HTTP-Status. Der Kernel macht daraus eine JSON-Antwort:
 * {"error": {"code": "not_found", "message": "Nicht gefunden"}}
 *
 * "code" ist maschinenlesbar (für das React-Frontend), "message" ist für Menschen.
 */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers zusätzliche Antwort-Header */
    public function __construct(
        private readonly int $status,
        private readonly string $errorCode,
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
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

    /** @param list<string> $allowed erlaubte Methoden für diese URL */
    public static function methodNotAllowed(array $allowed): self
    {
        return new self(405, 'method_not_allowed', 'Methode nicht erlaubt', ['Allow' => implode(', ', $allowed)]);
    }
}
