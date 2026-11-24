<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use Throwable;

/**
 * Einfacher Datei-Logger (eine Zeile pro Eintrag, Kontext als JSON).
 * Ausnahmen im Kontext werden mit Klasse, Meldung, Fundstelle und Stacktrace protokolliert.
 */
final class Logger
{
    private const LEVELS = ['debug' => 100, 'info' => 200, 'warning' => 300, 'error' => 400];

    private string $minLevel;

    public function __construct(private readonly string $path, string $minLevel = 'debug')
    {
        $minLevel = strtolower($minLevel);
        $this->minLevel = isset(self::LEVELS[$minLevel]) ? $minLevel : 'debug';
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);

        if (!isset(self::LEVELS[$level])) {
            throw new InvalidArgumentException(sprintf('Unbekanntes Log-Level: %s', $level));
        }

        if (self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $line = sprintf('[%s] %s: %s', date('Y-m-d H:i:s'), strtoupper($level), $message);

        if ($context !== []) {
            $line .= ' ' . json_encode(
                $this->normalize($context),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
            );
        }

        $directory = dirname($this->path);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log($line);

            return;
        }

        if (@file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log($line);
        }
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'class'   => $value::class,
                'message' => $value->getMessage(),
                'file'    => $value->getFile() . ':' . $value->getLine(),
                'trace'   => $value->getTraceAsString(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        if (is_object($value)) {
            return $value::class;
        }

        return $value;
    }
}
