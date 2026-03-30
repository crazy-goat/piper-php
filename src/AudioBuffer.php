<?php

declare(strict_types=1);

namespace OnnxTTS;

use OnnxTTS\Exception\AudioException;
use OnnxTTS\Exception\CompressionException;

class AudioBuffer
{
    private array $data;
    private int $sampleRate;
    private int $channels;

    public function __construct(array $data, int $sampleRate, int $channels = 1)
    {
        $this->data = $data;
        $this->sampleRate = $sampleRate;
        $this->channels = $channels;
    }

    public static function fromFloatArray(array $data, int $sampleRate, int $channels = 1): self
    {
        return new self($data, $sampleRate, $channels);
    }

    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    public function getChannels(): int
    {
        return $this->channels;
    }

    public function getDuration(): float
    {
        return count($this->data) / ($this->sampleRate * $this->channels);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toWav(): string
    {
        $pcmData = $this->floatToPCM16($this->data);

        $header = $this->createWavHeader(
            strlen($pcmData),
            $this->channels,
            $this->sampleRate,
            16
        );

        return $header . $pcmData;
    }

    public function toMp3(int $bitrate = 192): string
    {
        throw new CompressionException('mp3', 'MP3 compression not yet implemented. Use WAV format.');
    }

    public function toOgg(int $quality = 5): string
    {
        throw new CompressionException('ogg', 'OGG compression not yet implemented. Use WAV format.');
    }

    public function save(string $path, string $format = 'wav'): void
    {
        $audio = match ($format) {
            'wav' => $this->toWav(),
            'mp3' => $this->toMp3(),
            'ogg' => $this->toOgg(),
            default => throw new AudioException("Unsupported format: {$format}")
        };

        $result = file_put_contents($path, $audio, LOCK_EX);
        if ($result === false) {
            throw new AudioException("Failed to save audio to: {$path}");
        }
    }

    private function floatToPCM16(array $floatData): string
    {
        $pcmData = '';
        foreach ($floatData as $sample) {
            // Clamp to [-1.0, 1.0]
            $sample = max(-1.0, min(1.0, $sample));
            // Convert to 16-bit signed integer
            $pcm = (int) ($sample * 32767);
            // Pack as little-endian 16-bit
            $pcmData .= pack('v', $pcm);
        }
        return $pcmData;
    }

    private function createWavHeader(int $dataSize, int $channels, int $sampleRate, int $bitsPerSample): string
    {
        $byteRate = $sampleRate * $channels * ($bitsPerSample / 8);
        $blockAlign = $channels * ($bitsPerSample / 8);
        $totalSize = 36 + $dataSize;

        return pack('A4V', 'RIFF', $totalSize)
            . pack('A4', 'WAVE')
            . pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample)
            . pack('A4V', 'data', $dataSize);
    }
}
