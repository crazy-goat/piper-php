<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use OnnxTTS\ModelManager;
use OnnxTTS\Exception\ModelNotFoundException;

class ModelManagerTest extends TestCase
{
    private string $cacheDir;
    private ModelManager $manager;
    
    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/onnx-tts-test-' . uniqid();
        mkdir($this->cacheDir, 0777, true);
        $this->manager = new ModelManager($this->cacheDir);
    }
    
    protected function tearDown(): void
    {
        // Clean up
        if (is_dir($this->cacheDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($this->cacheDir);
        }
    }
    
    public function testConstructorCreatesCacheDir(): void
    {
        $newDir = $this->cacheDir . '/new-cache';
        new ModelManager($newDir);
        $this->assertDirectoryExists($newDir);
        rmdir($newDir);
    }
    
    public function testIsDownloadedReturnsFalseForMissingModel(): void
    {
        $this->assertFalse($this->manager->isDownloaded('nonexistent-model'));
    }
    
    public function testIsDownloadedReturnsTrueForExistingModel(): void
    {
        // Create fake model structure
        $modelDir = $this->cacheDir . '/test-model';
        mkdir($modelDir, 0777, true);
        file_put_contents($modelDir . '/model.onnx', 'fake onnx data');
        file_put_contents($modelDir . '/config.json', '{}');
        
        $this->assertTrue($this->manager->isDownloaded('test-model'));
    }
    
    public function testGetPathReturnsCorrectPath(): void
    {
        $path = $this->manager->getPath('my-model');
        $this->assertStringContainsString('my-model', $path);
        $this->assertStringContainsString($this->cacheDir, $path);
    }
    
    public function testListAvailableReturnsEmptyArrayInitially(): void
    {
        $models = $this->manager->listAvailable();
        $this->assertIsArray($models);
        $this->assertEmpty($models);
    }
    
    public function testListAvailableReturnsDownloadedModels(): void
    {
        // Create fake models
        mkdir($this->cacheDir . '/model1', 0777, true);
        file_put_contents($this->cacheDir . '/model1/model.onnx', 'data');
        file_put_contents($this->cacheDir . '/model1/config.json', '{}');
        
        mkdir($this->cacheDir . '/model2', 0777, true);
        file_put_contents($this->cacheDir . '/model2/model.onnx', 'data');
        file_put_contents($this->cacheDir . '/model2/config.json', '{}');
        
        $models = $this->manager->listAvailable();
        $this->assertCount(2, $models);
        $this->assertContains('model1', $models);
        $this->assertContains('model2', $models);
    }
}
