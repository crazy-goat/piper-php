<?php

declare(strict_types=1);

namespace OnnxTTS;

use OnnxTTS\Models\PiperModel;
use OnnxTTS\Exception\ModelNotFoundException;
use OnnxTTS\Exception\UnsupportedModelException;

class TTS
{
    private OnnxRuntime $runtime;
    private ModelManager $modelManager;
    private ?TTSModel $currentModel = null;
    private ?string $currentModelId = null;
    private ?string $speakerId = null;
    private float $speed = 1.0;
    private ?string $language = null;

    public function __construct(OnnxRuntime $runtime, ModelManager $modelManager)
    {
        $this->runtime = $runtime;
        $this->modelManager = $modelManager;
    }

    public function model(string $modelId): self
    {
        $this->currentModelId = $modelId;
        $this->currentModel = null; // Will be loaded on first use
        return $this;
    }

    public function speaker(string $speakerId): self
    {
        $this->speakerId = $speakerId;
        return $this;
    }

    public function speed(float $factor): self
    {
        $this->speed = max(0.5, min(2.0, $factor));
        return $this;
    }

    public function language(string $lang): self
    {
        $this->language = $lang;
        return $this;
    }

    public function speak(string $text): AudioBuffer
    {
        $model = $this->getOrLoadModel();

        // Apply settings
        if ($this->speakerId !== null && method_exists($model, 'setSpeaker')) {
            $model->setSpeaker($this->speakerId);
        }

        if (method_exists($model, 'setSpeed')) {
            $model->setSpeed($this->speed);
        }

        return $model->synthesize($text);
    }

    public function speakStream(string $text): \Generator
    {
        // For now, just yield the full result
        // In future, could implement true streaming
        yield $this->speak($text);
    }

    public function save(string $path, string $format = 'wav'): void
    {
        throw new \BadMethodCallException(
            'Call speak() first to generate audio, then use AudioBuffer::save()'
        );
    }

    private function getOrLoadModel(): TTSModel
    {
        if ($this->currentModel !== null) {
            return $this->currentModel;
        }

        if ($this->currentModelId === null) {
            throw new \RuntimeException('No model selected. Call model() first.');
        }

        // Check if model is downloaded
        if (!$this->modelManager->isDownloaded($this->currentModelId)) {
            throw new ModelNotFoundException($this->currentModelId);
        }

        $modelPath = $this->modelManager->getPath($this->currentModelId);
        $configPath = $modelPath . '/config.json';
        $config = json_decode(file_get_contents($configPath), true);

        // Determine model type from config
        $modelType = $config['model_type'] ?? 'piper';

        $this->currentModel = match ($modelType) {
            'piper' => new PiperModel($this->runtime, $modelPath),
            default => throw new UnsupportedModelException($modelType)
        };

        $this->currentModel->load();

        return $this->currentModel;
    }
}
