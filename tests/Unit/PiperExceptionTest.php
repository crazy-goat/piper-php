<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Unit;

use CrazyGoat\PiperTTS\Exception\PiperException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PiperExceptionTest extends TestCase
{
    public function test_extends_runtime_exception(): void
    {
        $exception = new PiperException('test message');

        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_message_is_set(): void
    {
        $exception = new PiperException('libpiper not found');

        self::assertSame('libpiper not found', $exception->getMessage());
    }

    public function test_can_be_thrown_and_caught(): void
    {
        $this->expectException(PiperException::class);
        $this->expectExceptionMessage('custom error');

        throw new PiperException('custom error');
    }
}
