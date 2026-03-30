<?php

declare(strict_types=1);

namespace FFI;

/**
 * PHPStan stub for FFI\CType.
 *
 * Represents a C type definition used by FFI.
 */
class CType
{
    public function getName(): string {}

    public function getKind(): int {}

    public function getSize(): int {}

    public function getAlignment(): int {}
}
