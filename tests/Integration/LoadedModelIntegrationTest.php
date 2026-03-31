<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Integration;

use CrazyGoat\PiperTTS\AudioChunk;
use CrazyGoat\PiperTTS\Exception\PiperException;
use CrazyGoat\PiperTTS\LoadedModel;
use CrazyGoat\PiperTTS\PiperTTS;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class LoadedModelIntegrationTest extends TestCase
{
    private static LoadedModel $model;

    public static function setUpBeforeClass(): void
    {
        $modelsPath = __DIR__ . '/../../models';

        if (!is_dir($modelsPath) || !is_file($modelsPath . '/en_US-lessac-low.onnx')) {
            self::markTestSkipped('Test voice model not found. Run: make test-model');
        }

        $piper = new PiperTTS($modelsPath);
        self::$model = $piper->loadModel('en_US-lessac-low', warmUp: true);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$model)) {
            self::$model->free();
        }
    }

    public function test_speak_returns_wav_bytes(): void
    {
        $wav = self::$model->speak('Hello world');

        self::assertNotEmpty($wav);
        self::assertSame('RIFF', substr($wav, 0, 4));
        self::assertSame('WAVE', substr($wav, 8, 4));
    }

    public function test_speak_with_speed(): void
    {
        $normal = self::$model->speak('Hello world', speed: 1.0);
        $fast = self::$model->speak('Hello world', speed: 2.0);

        self::assertLessThan(strlen($normal), strlen($fast));
    }

    public function test_speak_streaming_yields_chunks(): void
    {
        $chunks = iterator_to_array(self::$model->speakStreaming('Hello world'));

        self::assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            self::assertInstanceOf(AudioChunk::class, $chunk);
            self::assertGreaterThan(0, $chunk->sampleRate);
        }

        $lastChunk = end($chunks);
        self::assertTrue($lastChunk->isLast);
    }

    public function test_speak_streaming_empty_text_throws(): void
    {
        $this->expectException(PiperException::class);
        $this->expectExceptionMessage('Text cannot be empty');

        iterator_to_array(self::$model->speakStreaming(''));
    }

    public function test_speak_after_free_throws(): void
    {
        $piper = new PiperTTS(__DIR__ . '/../../models');
        $tempModel = $piper->loadModel('en_US-lessac-low');
        $tempModel->free();

        $this->expectException(PiperException::class);
        $this->expectExceptionMessage('Model has been freed');

        $tempModel->speak('should fail');
    }

    public function test_warm_up_returns_positive_time(): void
    {
        $piper = new PiperTTS(__DIR__ . '/../../models');
        $tempModel = $piper->loadModel('en_US-lessac-low');

        $ms = $tempModel->warmUp();

        self::assertGreaterThan(0, $ms);

        $tempModel->free();
    }
}
