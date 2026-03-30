<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use OnnxTTS\AudioBuffer;

class AudioBufferTest extends TestCase
{
    public function testFromFloatArrayCreatesBuffer(): void
    {
        $data = [0.0, 0.5, 1.0, -0.5, -1.0];
        $buffer = AudioBuffer::fromFloatArray($data, 22050);

        $this->assertInstanceOf(AudioBuffer::class, $buffer);
        $this->assertEquals(22050, $buffer->getSampleRate());
        $this->assertEqualsWithDelta(0.00022675, $buffer->getDuration(), 0.00001);
    }

    public function testToWavCreatesValidHeader(): void
    {
        $data = [0.0, 0.5, 1.0, -0.5, -1.0];
        $buffer = AudioBuffer::fromFloatArray($data, 22050);
        $wav = $buffer->toWav();

        $this->assertStringStartsWith('RIFF', $wav);
        $this->assertStringContainsString('WAVE', $wav);
        $this->assertStringContainsString('fmt ', $wav);
        $this->assertStringContainsString('data', $wav);
    }

    public function testSaveCreatesFile(): void
    {
        $data = [0.0, 0.5, 1.0];
        $buffer = AudioBuffer::fromFloatArray($data, 22050);

        $tempFile = sys_get_temp_dir() . '/test_audio.wav';
        $buffer->save($tempFile, 'wav');

        $this->assertFileExists($tempFile);
        $this->assertGreaterThan(44, filesize($tempFile)); // WAV header is 44 bytes

        unlink($tempFile);
    }
}
