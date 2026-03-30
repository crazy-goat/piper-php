<?php

declare(strict_types=1);

namespace OnnxTTS;

use OnnxTTS\Exception\ModelCorruptedException;
use OnnxTTS\Exception\ModelNotFoundException;

abstract class TTSModel
{
    protected OnnxRuntime $runtime;
    protected string $modelDir;
    protected ?OrtSession $session = null;
    protected array $config = [];
    protected ?TextPreprocessor $preprocessor = null;
    
    public function __construct(OnnxRuntime $runtime, string $modelDir)
    {
        $this->runtime = $runtime;
        $this->modelDir = rtrim($modelDir, '/');
    }
    
    public function load(): void
    {
        if (!is_dir($this->modelDir)) {
            throw new ModelNotFoundException(basename($this->modelDir));
        }
        
        $this->loadConfig();
        $this->validateConfig();
        $this->loadModel();
        $this->initializePreprocessor();
    }
    
    abstract public function synthesize(string $text): AudioBuffer;
    
    public function getSampleRate(): int
    {
        return $this->config['sample_rate'] ?? 22050;
    }
    
    public function getSpeakers(): array
    {
        return $this->config['speakers'] ?? [];
    }
    
    public function getLanguages(): array
    {
        return [$this->config['language'] ?? 'unknown'];
    }
    
    protected function loadConfig(): void
    {
        $configPath = $this->modelDir . '/config.json';
        
        if (!file_exists($configPath)) {
            throw new ModelCorruptedException(
                basename($this->modelDir),
                'Missing config.json'
            );
        }
        
        $configData = file_get_contents($configPath);
        $this->config = json_decode($configData, true, 512, JSON_THROW_ON_ERROR);
    }
    
    protected function validateConfig(): void
    {
        // Piper uses different config structure - check for audio.sample_rate or num_speakers
        $hasPiperFields = isset($this->config['audio']['sample_rate']) || 
                          isset($this->config['num_speakers']);
        
        if (!$hasPiperFields && !isset($this->config['model_type'])) {
            throw new ModelCorruptedException(
                basename($this->modelDir),
                "Missing required config fields (expected model_type or Piper-specific fields)"
            );
        }
    }
    
    protected function loadModel(): void
    {
        $modelPath = $this->findModelFile();
        
        if ($modelPath === null) {
            throw new ModelCorruptedException(
                basename($this->modelDir),
                'No .onnx model file found'
            );
        }
        
        $this->session = $this->runtime->createSession($modelPath);
    }
    
    protected function findModelFile(): ?string
    {
        // Look for model.onnx first
        $modelPath = $this->modelDir . '/model.onnx';
        if (file_exists($modelPath)) {
            return $modelPath;
        }
        
        // Look for any .onnx file
        $files = glob($this->modelDir . '/*.onnx');
        if (count($files) > 0) {
            return $files[0];
        }
        
        return null;
    }
    
    protected function initializePreprocessor(): void
    {
        $language = $this->config['language'] ?? 'en';
        $this->preprocessor = new TextPreprocessor($language);
    }
    
    protected function preprocessText(string $text): string
    {
        if ($this->preprocessor === null) {
            return $text;
        }
        
        return $this->preprocessor->normalize($text);
    }
}
