<?php

declare(strict_types=1);

namespace App\Http;

/** HTTP-Antwort. Wird vom Kernel erzeugt und mit send() ausgegeben. */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $body = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );

        return new self($body, $status, $headers + [
            'Content-Type'           => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Erfolgsantwort im einheitlichen Format laut API-Vertrag:
     * {"statuscode": 200, "error": false, "message": "OK", "response": <Daten>}
     * Der HTTP-Status ist derselbe Wert wie "statuscode".
     *
     * @param array<string, string> $headers
     */
    public static function success(mixed $data = null, int $status = 200, string $message = 'OK', array $headers = []): self
    {
        return self::json([
            'statuscode' => $status,
            'error'      => false,
            'message'    => $message,
            'response'   => $data,
        ], $status, $headers);
    }

    /**
     * Fehlerantwort im einheitlichen Format: {"statuscode": 404, "error": true, "message": "...", "response": null}.
     * $data bleibt normalerweise null; nur bei Validierungsfehlern ({"errors": {...}}) und im Debug-Modus gefüllt.
     *
     * @param array<string, string> $headers
     */
    public static function failure(int $status, string $message, mixed $data = null, array $headers = []): self
    {
        return self::json([
            'statuscode' => $status,
            'error'      => true,
            'message'    => $message,
            'response'   => $data,
        ], $status, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->body;
    }
}
