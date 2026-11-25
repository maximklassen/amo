<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

/**
 * Eingehende HTTP-Anfrage. Request::capture() liest sie aus den PHP-Superglobals,
 * in Tests wird sie direkt per Konstruktor gebaut.
 */
final class Request
{
    public readonly string $method;

    /** Pfad ohne Query-String und ohne abschließenden Slash, z. B. "/api/health" */
    public readonly string $path;

    /** @var array<string, string> Header-Namen in Kleinbuchstaben */
    public readonly array $headers;

    /** @var array<string, mixed>|null */
    private ?array $parsedBody = null;

    /** @var array<string, string> */
    private array $routeParams = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     * @param array<string, mixed>  $form    Formulardaten ($_POST), falls kein JSON gesendet wurde
     */
    public function __construct(
        string $method,
        string $path,
        public readonly array $query = [],
        array $headers = [],
        private readonly string $rawBody = '',
        private readonly array $form = [],
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[str_replace('_', '-', strtolower($key))] = $value;
            }
        }

        // Apache reicht den Authorization-Header je nach Konfiguration mit REDIRECT_-Präfix durch
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $headers,
            (string) file_get_contents('php://input'),
            $_POST,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** Token aus "Authorization: Bearer <token>" oder null. */
    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');

        if ($header !== null && preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Der Request-Body als Array. JSON wird bei Content-Type "application/json" gelesen,
     * sonst werden die Formulardaten verwendet. Ungültiges JSON ergibt HTTP 400.
     *
     * @return array<string, mixed>
     */
    public function body(): array
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        $contentType = strtolower((string) $this->header('content-type', ''));

        if ($this->rawBody !== '' && str_contains($contentType, 'json')) {
            try {
                $decoded = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw HttpException::badRequest('invalid_json', 'Der Request-Body enthält kein gültiges JSON');
            }

            if (!is_array($decoded)) {
                throw HttpException::badRequest('invalid_json', 'Der Request-Body muss ein JSON-Objekt sein');
            }

            return $this->parsedBody = $decoded;
        }

        return $this->parsedBody = $this->form;
    }

    /** Wert aus dem Body, ersatzweise aus der Query-String. */
    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();

        return array_key_exists($key, $body) ? $body[$key] : ($this->query[$key] ?? $default);
    }

    /**
     * Query und Body zusammen (der Body hat Vorrang).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body() + $this->query;
    }

    /** Platzhalter aus der Route, z. B. {id} in /api/parzellen/{id}. */
    public function param(string $name, ?string $default = null): ?string
    {
        return $this->routeParams[$name] ?? $default;
    }

    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /** Platz für Daten von Middleware, z. B. den angemeldeten Benutzer. */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
