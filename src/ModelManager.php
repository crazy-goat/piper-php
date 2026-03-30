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
            if (!mkdir($modelDir, 0755, true) && !is_dir($modelDir)) {
                throw new \RuntimeException("Failed to create directory: {$modelDir}");
            }
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
        if (preg_match('/[\/\\\\.]/', $modelId)) {
            throw new \InvalidArgumentException("Invalid model ID: {$modelId}");
        }
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
        $onnxFiles = glob($modelDir . '/*.onnx');
        $hasOnnx = file_exists($modelDir . '/model.onnx') ||
                   ($onnxFiles !== false && count($onnxFiles) > 0);
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
        
        try {
            // Download config.json
            $configUrl = "https://huggingface.co/{$repo}/resolve/main/{$path}/config.json";
            $this->downloadFile($configUrl, $modelDir . '/config.json');

            // Download model.onnx
            $modelUrl = "https://huggingface.co/{$repo}/resolve/main/{$path}/model.onnx";
            $this->downloadFile($modelUrl, $modelDir . '/model.onnx');
        } catch (\Exception $e) {
            // Clean up partial download
            $this->recursiveDelete($modelDir);
            throw $e;
        }
    }
    
    private function downloadFile(string $url, string $destination): void
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 300,
                'follow_location' => true,
            ]
        ]);

        $source = @fopen($url, 'r', false, $context);
        if ($source === false) {
            throw new NetworkException($url, 'Failed to open download stream');
        }

        $target = @fopen($destination, 'w');
        if ($target === false) {
            fclose($source);
            throw new \RuntimeException("Failed to create file: {$destination}");
        }

        while (!feof($source)) {
            $chunk = fread($source, 8192);
            if ($chunk === false || fwrite($target, $chunk) === false) {
                fclose($source);
                fclose($target);
                unlink($destination);
                throw new NetworkException($url, 'Failed during download');
            }
        }

        fclose($source);
        fclose($target);
    }
    
    private function ensureCacheDirExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new \RuntimeException("Failed to create directory: {$this->cacheDir}");
            }
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
