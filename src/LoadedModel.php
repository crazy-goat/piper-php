<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS;

use CrazyGoat\PiperTTS\Exception\PiperException;
use FFI;
use FFI\CData;

/**
 * Represents a loaded Piper TTS voice model.
 * 
 * Use this class to synthesize speech with a specific voice.
 * The model remains loaded in memory until explicitly freed.
 */
final class LoadedModel
{
    public function __construct(
        private readonly FFI $piper,
        private readonly CData $synth,
        private readonly string $voice,
    ) {
    }

    public function __destruct()
    {
        $this->free();
    }

    /**
     * Free the loaded model and release resources.
     */
    public function free(): void
    {
        if (isset($this->synth) && !FFI::isNull($this->synth)) {
            $this->piper->piper_free($this->synth);
        }
    }

    /**
     * Warm up the model to avoid first-chunk delay.
     *
     * ONNX Runtime has a significant initialization overhead on first inference.
     * Calling this method after loading the model ensures subsequent
     * synthesis calls are fast.
     *
     * @return int Time taken for warm-up in milliseconds
     */
    public function warmUp(): int
    {
        $t0 = microtime(true);
        // Dummy synthesis - result is discarded
        foreach ($this->speakStreaming('warm up') as $chunk) {
            // Consume but ignore chunks
        }
        return (int) round((microtime(true) - $t0) * 1000);
    }

    /**
     * Synthesize text to WAV audio.
     *
     * Collects all audio chunks and returns a complete WAV file.
     * For streaming, use speakStreaming() instead.
     *
     * @param string $text      Text to speak
     * @param float  $speed     Speech speed multiplier (1.0 = normal, 2.0 = twice as fast)
     * @param int    $speakerId Speaker ID for multi-speaker models (0 = default)
     *
     * @return string Raw WAV file bytes
     */
    public function speak(string $text, float $speed = 1.0, int $speakerId = 0): string
    {
        $pcmData = '';
        $sampleRate = 0;

        foreach ($this->speakStreaming($text, $speed, $speakerId) as $chunk) {
            $pcmData .= $chunk->pcmData;
            $sampleRate = $chunk->sampleRate;
        }

        return $this->buildWav($pcmData, $sampleRate);
    }

    /**
     * Synthesize text to audio, yielding chunks as they are generated.
     *
     * Each chunk contains raw 16-bit PCM data for one sentence/segment.
     * This allows streaming audio to a client before synthesis is complete.
     *
     * @param string $text      Text to speak
     * @param float  $speed     Speech speed multiplier (1.0 = normal, 2.0 = twice as fast)
     * @param int    $speakerId Speaker ID for multi-speaker models (0 = default)
     *
     * @return \Generator<int, AudioChunk>
     */
    public function speakStreaming(string $text, float $speed = 1.0, int $speakerId = 0): \Generator
    {
        if ($text === '') {
            throw new PiperException('Text cannot be empty');
        }

        if (FFI::isNull($this->synth)) {
            throw new PiperException('Model has been freed');
        }

        $options = $this->piper->piper_default_synthesize_options($this->synth);
        $options->speaker_id = $speakerId;

        if ($speed > 0.0 && $speed !== 1.0) {
            $options->length_scale = 1.0 / $speed;
        }

        $rc = $this->piper->piper_synthesize_start($this->synth, $text, FFI::addr($options));
        if ($rc !== 0) {
            throw new PiperException("piper_synthesize_start failed (rc={$rc})");
        }

        $chunk = $this->piper->new('piper_audio_chunk');

        while (true) {
            $rc = $this->piper->piper_synthesize_next($this->synth, FFI::addr($chunk));

            if ($rc === -1) {
                throw new PiperException('piper_synthesize_next failed');
            }

            $numSamples = $chunk->num_samples;
            $pcmData = '';

            for ($i = 0; $i < $numSamples; $i++) {
                $sample = $chunk->samples[$i];
                if ($sample > 1.0) {
                    $sample = 1.0;
                } elseif ($sample < -1.0) {
                    $sample = -1.0;
                }
                $pcmData .= pack('v', ((int)($sample * 32767)) & 0xFFFF);
            }

            $isLast = $rc === 1;

            yield new AudioChunk($pcmData, $chunk->sample_rate, $isLast);

            if ($isLast) {
                break;
            }
        }
    }

    private function buildWav(string $pcmData, int $sampleRate): string
    {
        $dataSize = strlen($pcmData);

        return 'RIFF'
            . pack('V', 36 + $dataSize)   // file size - 8
            . 'WAVE'
            . 'fmt '
            . pack('V', 16)               // subchunk1 size
            . pack('v', 1)                // PCM format
            . pack('v', 1)                // mono
            . pack('V', $sampleRate)      // sample rate
            . pack('V', $sampleRate * 2)  // byte rate (16-bit mono)
            . pack('v', 2)                // block align
            . pack('v', 16)               // bits per sample
            . 'data'
            . pack('V', $dataSize)
            . $pcmData;
    }
}
