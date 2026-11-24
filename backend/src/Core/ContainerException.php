<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Wird geworfen, wenn der Container eine Abhängigkeit nicht auflösen kann. */
final class ContainerException extends RuntimeException
{
}
