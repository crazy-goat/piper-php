<?php

declare(strict_types=1);

namespace OnnxTTS;

use OnnxTTS\Exception\ModelNotFoundException;
use OnnxTTS\Exception\NetworkException;

class ModelManager
{
    private string $cacheDir;
    
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->ensureCacheDirExists();
    }
    
    public function listAvailable(): array
    {
        $models = [];
        
        if (!is_dir($this->cacheDir)) {
            return $models;
        }
        
        $iterator = new \DirectoryIterator($this->cacheDir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $modelId = $fileinfo->getFilename();
                if ($this->isValidModel($modelId)) {
                    $models[] = $modelId;
                }
            }
        }
        
        sort($models);
        return $models;
    }
    
    public function listRemote(): array
    {
        // Return list of known models from HuggingFace
        return [
            'piper-pl' => [
                'source' => 'huggingface',
                'repo' => 'rhasspy/piper-voices',
                'path' => 'pl/pl_PL/gosia/medium',
            ],
            'piper-en' => [
                'source' => 'huggingface',
                'repo' => 'rhasspy/piper-voices',
                'path' => 'en/en_US/amy/medium',
            ],
        ];
    }
    
    public function download(string $modelId, string $source = 'huggingface'): void
    {
        $modelDir = $this->getPath($modelId);
        
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        switch ($source) {
            case 'huggingface':
                $this->downloadFromHuggingFace($modelId, $modelDir);
                break;
            default:
                throw new \InvalidArgumentException("Unknown source: {$source}");
        }
    }
    
    public function getPath(string $modelId): string
    {
        return $this->cacheDir . '/' . $modelId;
    }
    
    public function isDownloaded(string $modelId): bool
    {
        return $this->isValidModel($modelId);
    }
    
    public function delete(string $modelId): void
    {
        $modelDir = $this->getPath($modelId);
        
        if (!is_dir($modelDir)) {
            throw new ModelNotFoundException($modelId);
        }
        
        $this->recursiveDelete($modelDir);
    }
    
    private function isValidModel(string $modelId): bool
    {
        $modelDir = $this->getPath($modelId);
        
        if (!is_dir($modelDir)) {
            return false;
        }
        
        // Check for required files
        $hasOnnx = file_exists($modelDir . '/model.onnx') || 
                   count(glob($modelDir . '/*.onnx')) > 0;
        $hasConfig = file_exists($modelDir . '/config.json');
        
        return $hasOnnx && $hasConfig;
    }
    
    private function downloadFromHuggingFace(string $modelId, string $modelDir): void
    {
        $remoteModels = $this->listRemote();
        
        if (!isset($remoteModels[$modelId])) {
            throw new ModelNotFoundException($modelId);
        }
        
        $modelInfo = $remoteModels[$modelId];
        $repo = $modelInfo['repo'];
        $path = $modelInfo['path'];
        
        // Download config.json
        $configUrl = "https://huggingface.co/{$repo}/resolve/main/{$path}/config.json";
        $this->downloadFile($configUrl, $modelDir . '/config.json');
        
        // Download model.onnx
        $modelUrl = "https://huggingface.co/{$repo}/resolve/main/{$path}/model.onnx";
        $this->downloadFile($modelUrl, $modelDir . '/model.onnx');
    }
    
    private function downloadFile(string $url, string $destination): void
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 300, // 5 minutes for large models
                'follow_location' => true,
            ]
        ]);
        
        $data = @file_get_contents($url, false, $context);
        
        if ($data === false) {
            throw new NetworkException($url, 'Failed to download file');
        }
        
        $result = file_put_contents($destination, $data, LOCK_EX);
        
        if ($result === false) {
            throw new \RuntimeException("Failed to save file: {$destination}");
        }
    }
    
    private function ensureCacheDirExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function recursiveDelete(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($dir);
    }
}
