<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit;

use OnnxTTS\Exception\LibraryNotFoundException;
use OnnxTTS\OnnxRuntime;
use PHPUnit\Framework\TestCase;

class OnnxRuntimeTest extends TestCase
{
    public function testConstructorThrowsOnMissingLibrary(): void
    {
        $this->expectException(LibraryNotFoundException::class);
        new OnnxRuntime('/nonexistent/path/libonnxruntime.so');
    }

    public function testGetVersionReturnsString(): void
    {
        $libraryPath = getenv('ORT_LIBRARY') ?: '/usr/local/lib/libonnxruntime.so';

        if (!file_exists($libraryPath)) {
            $this->markTestSkipped('ONNX Runtime not installed');
        }

        $runtime = new OnnxRuntime($libraryPath);
        $version = $runtime->getVersion();

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', $version);
    }
}
