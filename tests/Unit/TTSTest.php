<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use OnnxTTS\TTS;
use OnnxTTS\OnnxRuntime;
use OnnxTTS\ModelManager;

class TTSTest extends TestCase
{
    private ?string $tempDir = null;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/onnx-tts-api-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->tempDir && is_dir($this->tempDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testFluentInterfaceReturnsSelf(): void
    {
        if (!file_exists(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21')) {
            $this->markTestSkipped('ONNX Runtime not installed');
        }

        $runtime = new OnnxRuntime(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21');
        $manager = new ModelManager($this->tempDir);
        $tts = new TTS($runtime, $manager);

        $result = $tts->model('test-model');
        $this->assertSame($tts, $result);

        $result = $tts->speaker('default');
        $this->assertSame($tts, $result);

        $result = $tts->speed(1.2);
        $this->assertSame($tts, $result);
    }
}
