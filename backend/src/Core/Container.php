<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Minimaler Dependency-Injection-Container.
 *
 * - bind():      neue Instanz bei jedem get()
 * - singleton(): eine gemeinsame Instanz
 * - instance():  fertiges Objekt registrieren
 * - Alles andere wird per Reflection automatisch zusammengesteckt (Autowiring):
 *   Konstruktor-Parameter mit Klassen-Typ werden rekursiv aus dem Container geholt.
 */
final class Container
{
    /** @var array<string, array{0: Closure, 1: bool}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $resolving = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = [$factory, false];
        unset($this->instances[$id]);
    }

    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = [$factory, true];
        unset($this->instances[$id]);
    }

    /**
     * Klassen, die per Autowiring gebaut werden, aber nur einmal pro Anfrage existieren sollen
     * (z. B. Auth: Middleware und Controller müssen dasselbe Objekt sehen).
     */
    public function share(string ...$classes): void
    {
        foreach ($classes as $class) {
            $this->singleton($class, fn (): object => $this->autowire($class));
        }
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException(sprintf('Zirkuläre Abhängigkeit bei %s', $id));
        }

        $this->resolving[$id] = true;

        try {
            if (isset($this->bindings[$id])) {
                [$factory, $shared] = $this->bindings[$id];
                $object = $factory($this);

                if ($shared) {
                    $this->instances[$id] = $object;
                }

                return $object;
            }

            return $this->autowire($id);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException(sprintf('Klasse oder Binding nicht gefunden: %s', $class));
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('%s kann nicht instanziiert werden (abstrakt, ohne Binding)', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new ContainerException(sprintf(
                    'Parameter $%s von %s kann nicht automatisch aufgelöst werden',
                    $parameter->getName(),
                    $class,
                ));
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
