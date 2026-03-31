<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Integration;

use CrazyGoat\PiperTTS\Exception\PiperException;
use CrazyGoat\PiperTTS\LoadedModel;
use CrazyGoat\PiperTTS\PiperTTS;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class PiperTTSIntegrationTest extends TestCase
{
    private static PiperTTS $piper;
    private static string $modelsPath;

    public static function setUpBeforeClass(): void
    {
        self::$modelsPath = __DIR__ . '/../../models';

        if (!is_dir(self::$modelsPath)) {
            self::markTestSkipped('Models directory not found. Run: make test-model');
        }

        $onnxPath = self::$modelsPath . '/en_US-lessac-low.onnx';
        if (!is_file($onnxPath)) {
            self::markTestSkipped('Test voice model not found. Run: make test-model');
        }

        self::$piper = new PiperTTS(self::$modelsPath);
    }

    public function test_load_model_returns_loaded_model(): void
    {
        $model = self::$piper->loadModel('en_US-lessac-low');

        self::assertInstanceOf(LoadedModel::class, $model);
        $model->free();
    }

    public function test_load_model_with_warmup(): void
    {
        $model = self::$piper->loadModel('en_US-lessac-low', warmUp: true);

        self::assertInstanceOf(LoadedModel::class, $model);
        $model->free();
    }

    public function test_load_nonexistent_voice_throws(): void
    {
        $this->expectException(PiperException::class);
        $this->expectExceptionMessage('Voice model not found');

        self::$piper->loadModel('nonexistent_voice');
    }

    public function test_voices_lists_installed_voices(): void
    {
        $voices = self::$piper->voices();

        self::assertIsArray($voices);
        self::assertNotEmpty($voices);

        $keys = array_map(fn($v) => $v->key, $voices);
        self::assertContains('en_US-lessac-low', $keys);
    }
}
