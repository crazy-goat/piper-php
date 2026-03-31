<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Unit;

use CrazyGoat\PiperTTS\Exception\PiperException;
use PHPUnit\Framework\TestCase;

final class LoadedModelValidationTest extends TestCase
{
    public function test_piper_exception_is_used_for_model_errors(): void
    {
        $exception = new PiperException('Model has been freed');

        self::assertSame('Model has been freed', $exception->getMessage());
    }

    public function test_piper_exception_for_empty_text(): void
    {
        $exception = new PiperException('Text cannot be empty');

        self::assertSame('Text cannot be empty', $exception->getMessage());
    }

    public function test_piper_exception_for_synthesize_failure(): void
    {
        $exception = new PiperException('piper_synthesize_next failed');

        self::assertSame('piper_synthesize_next failed', $exception->getMessage());
    }
}
