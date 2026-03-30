<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Integration;

use PHPUnit\Framework\TestCase;
use OnnxTTS\OnnxRuntime;
use OnnxTTS\ModelManager;
use OnnxTTS\TTS;
use OnnxTTS\AudioBuffer;
use OnnxTTS\TextPreprocessor;

class FullPipelineTest extends TestCase
{
    private ?string $tempDir = null;
    
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/onnx-tts-integration-' . uniqid();
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
    
    public function testAudioBufferPipeline(): void
    {
        // Test without ORT - just audio processing
        $data = [0.0, 0.5, 1.0, -0.5, -1.0];
        $buffer = AudioBuffer::fromFloatArray($data, 22050);
        
        $this->assertEquals(22050, $buffer->getSampleRate());
        
        $wav = $buffer->toWav();
        $this->assertStringStartsWith('RIFF', $wav);
        
        $tempFile = $this->tempDir . '/test.wav';
        $buffer->save($tempFile, 'wav');
        $this->assertFileExists($tempFile);
        $this->assertGreaterThan(44, filesize($tempFile));
    }
    
    public function testTextPreprocessorPipeline(): void
    {
        $preprocessor = new TextPreprocessor('pl');
        
        $result = $preprocessor->normalize('Witaj świecie');
        $this->assertEquals('Witaj świecie', $result);
        
        $result = $preprocessor->normalize('  Test   whitespace  ');
        $this->assertEquals('Test whitespace', $result);
    }
    
    public function testModelManagerPipeline(): void
    {
        $manager = new ModelManager($this->tempDir);
        
        // Initially empty
        $this->assertEmpty($manager->listAvailable());
        
        // Create fake model
        $modelDir = $this->tempDir . '/test-model';
        mkdir($modelDir, 0777, true);
        file_put_contents($modelDir . '/model.onnx', 'fake');
        file_put_contents($modelDir . '/config.json', '{}');
        
        // Should now be available
        $this->assertTrue($manager->isDownloaded('test-model'));
        $this->assertContains('test-model', $manager->listAvailable());
    }
}
