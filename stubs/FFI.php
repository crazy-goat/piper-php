<?php

/**
 * PHPStan stub for the FFI class.
 *
 * Overrides the built-in FFI stub to add:
 * - __call() magic method for dynamic C function dispatch via cdef()
 * - Proper isNull() signature accepting FFI\CData by reference
 *
 * FFI instances created via FFI::cdef() expose C functions as dynamic
 * instance methods. At runtime PHP dispatches these through internal
 * C-level magic, but PHPStan needs __call() declared to accept them.
 */
final class FFI
{
    public static function cdef(string $code = '', ?string $lib = null): FFI {}

    public static function load(string $filename): ?FFI {}

    public static function scope(string $name): FFI {}

    /**
     * @param FFI\CType|string $type
     */
    public function new(FFI\CType|string $type, bool $owned = true, bool $persistent = false): ?FFI\CData {}

    /** @prefer-ref $ptr */
    public static function free(FFI\CData $ptr): void {}

    /**
     * @param FFI\CData|int|float|bool|null $ptr
     * @prefer-ref $ptr
     */
    public function cast(FFI\CType|string $type, $ptr): ?FFI\CData {}

    public function type(string $type): ?FFI\CType {}

    /** @prefer-ref $ptr */
    public static function typeof(FFI\CData $ptr): FFI\CType {}

    /**
     * @param array<array-key, int<0, max>> $dimensions
     */
    public static function arrayType(FFI\CType $type, array $dimensions): FFI\CType {}

    /** @prefer-ref $ptr */
    public static function addr(FFI\CData $ptr): FFI\CData {}

    /** @prefer-ref $ptr */
    public static function sizeof(FFI\CData|FFI\CType $ptr): int {}

    /** @prefer-ref $ptr */
    public static function alignof(FFI\CData|FFI\CType $ptr): int {}

    /**
     * @param FFI\CData|string $from
     * @prefer-ref $to
     * @prefer-ref $from
     */
    public static function memcpy(FFI\CData $to, $from, int $size): void {}

    /**
     * @param string|FFI\CData $ptr1
     * @param string|FFI\CData $ptr2
     * @prefer-ref $ptr1
     * @prefer-ref $ptr2
     */
    public static function memcmp($ptr1, $ptr2, int $size): int {}

    /** @prefer-ref $ptr */
    public static function memset(FFI\CData $ptr, int $value, int $size): void {}

    /** @prefer-ref $ptr */
    public static function string(FFI\CData $ptr, ?int $size = null): string {}

    /**
     * @param FFI\CData $ptr
     * @prefer-ref $ptr
     */
    public static function isNull(FFI\CData $ptr): bool {}

    /**
     * Dynamic dispatch for C functions loaded via cdef()/load()/scope().
     *
     * At runtime, PHP's FFI dispatches calls to C functions bound by
     * cdef() through internal C-level magic. This __call declaration
     * allows PHPStan to accept those dynamic method calls.
     *
     * @param string       $name      C function name
     * @param list<mixed>  $arguments Function arguments
     * @return mixed Return value from the C function
     */
    public function __call(string $name, array $arguments): mixed {}
}
