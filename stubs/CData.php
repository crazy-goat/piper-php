<?php

namespace FFI;

/**
 * PHPStan stub for FFI\CData.
 *
 * Overrides the built-in CData stub to add __get/__set magic methods
 * as real methods (not just @method annotations) so PHPStan level 9
 * accepts dynamic property access on C struct fields.
 */
final class CData
{
    /**
     * Read a C struct field or dereference a pointer.
     *
     * @return mixed Field value (int, float, bool, string, CData, etc.)
     */
    public function __get(string $name): mixed {}

    /**
     * Write a C struct field.
     *
     * @param mixed $value Value to assign
     */
    public function __set(string $name, mixed $value): void {}

    /**
     * Check if a C struct field exists.
     */
    public function __isset(string $name): bool {}

    /**
     * Invoke a function pointer.
     */
    public function __invoke(mixed ...$args): mixed {}
}
