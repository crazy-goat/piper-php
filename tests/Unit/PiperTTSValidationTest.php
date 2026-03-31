<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Unit;

use CrazyGoat\PiperTTS\Exception\PiperException;
use CrazyGoat\PiperTTS\PiperTTS;
use PHPUnit\Framework\TestCase;

final class PiperTTSValidationTest extends TestCase
{
    public function test_throws_when_models_dir_does_not_exist(): void
    {
        $this->expectException(PiperException::class);
        $this->expectExceptionMessage('Models directory does not exist');

        new PiperTTS('/nonexistent/path/that/does/not/exist');
    }
}
