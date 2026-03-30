<?php

declare(strict_types=1);

namespace Decodo\PiperTTS;

use Decodo\PiperTTS\Exception\PiperException;
use FFI;

final class PiperTTS
{
    private FFI $piper;
    private string $resolvedEspeakDataPath;
    private string $resolvedOnnxrtPath;

    private const CDEF = <<<'CDEF'
    typedef struct piper_synthesizer piper_synthesizer;

    typedef struct piper_audio_chunk {
        const float *samples;
        size_t num_samples;
        int sample_rate;
        bool is_last;
        const uint32_t *phonemes;
        size_t num_phonemes;
        const int *phoneme_ids;
        size_t num_phoneme_ids;
        const int *alignments;
        size_t num_alignments;
    } piper_audio_chunk;

    typedef struct piper_synthesize_options {
        int speaker_id;
        float length_scale;
        float noise_scale;
        float noise_w_scale;
    } piper_synthesize_options;

    piper_synthesizer *piper_create(const char *model_path, const char *config_path,
                                    const char *espeak_data_path);
    void piper_free(piper_synthesizer *synth);
    piper_synthesize_options piper_default_synthesize_options(piper_synthesizer *synth);
    int piper_synthesize_start(piper_synthesizer *synth, const char *text,
                               const piper_synthesize_options *options);
    int piper_synthesize_next(piper_synthesizer *synth, piper_audio_chunk *chunk);
    CDEF;

    public function __construct(
        private readonly string $modelsPath,
        ?string $libpiperPath = null,
        ?string $onnxrtPath = null,
        ?string $espeakDataPath = null,
    ) {
        if (!is_dir($this->modelsPath)) {
            throw new PiperException("Models directory does not exist: {$this->modelsPath}");
        }

        $libpiperPath ??= $this->findLibpiper();
        $this->resolvedOnnxrtPath = $onnxrtPath ?? $this->findOnnxrt(dirname($libpiperPath));
        $this->resolvedEspeakDataPath = $espeakDataPath ?? $this->findEspeakData(dirname($libpiperPath));

        // onnxruntime must be loadable by the dynamic linker
        $onnxrtDir = dirname($this->resolvedOnnxrtPath);
        $ldPath = getenv('LD_LIBRARY_PATH') ?: '';
        if (!str_contains($ldPath, $onnxrtDir)) {
            putenv("LD_LIBRARY_PATH={$onnxrtDir}:{$ldPath}");
        }

        // Load onnxruntime first (libpiper depends on it), then libpiper
        FFI::cdef('', $this->resolvedOnnxrtPath);
        $this->piper = FFI::cdef(self::CDEF, $libpiperPath);
    }

    /**
     * List locally installed voices by scanning the models directory.
     *
     * @return VoiceInfo[]
     */
    public function voices(): array
    {
        $voices = [];
        $glob = glob($this->modelsPath . '/*.onnx');

        if ($glob === false) {
            return [];
        }

        foreach ($glob as $onnxPath) {
            $jsonPath = $onnxPath . '.json';
            if (!is_file($jsonPath)) {
                continue;
            }

            $key = basename($onnxPath, '.onnx');

            try {
                $voices[] = VoiceInfo::fromConfigFile($key, $jsonPath);
            } catch (\Throwable) {
                // Skip models with invalid/unreadable config
                continue;
            }
        }

        usort($voices, fn(VoiceInfo $a, VoiceInfo $b) => $a->key <=> $b->key);

        return $voices;
    }

    /**
     * Synthesize text to WAV audio.
     *
     * @param string $text      Text to speak
     * @param string $voice     Voice key (e.g. "pl_PL-gosia-medium")
     * @param float  $speed     Speech speed multiplier (1.0 = normal, 2.0 = twice as fast)
     * @param int    $speakerId Speaker ID for multi-speaker models (0 = default)
     *
     * @return string Raw WAV file bytes
     */
    public function speak(string $text, string $voice, float $speed = 1.0, int $speakerId = 0): string
    {
        if ($text === '') {
            throw new PiperException('Text cannot be empty');
        }

        $modelPath = $this->modelsPath . '/' . $voice . '.onnx';
        $configPath = $modelPath . '.json';

        if (!is_file($modelPath)) {
            throw new PiperException("Voice model not found: {$modelPath}");
        }
        if (!is_file($configPath)) {
            throw new PiperException("Voice config not found: {$configPath}");
        }

        // Create synthesizer
        $synth = $this->piper->piper_create($modelPath, $configPath, $this->resolvedEspeakDataPath);
        if (FFI::isNull($synth)) {
            throw new PiperException("piper_create failed for voice: {$voice}");
        }

        try {
            return $this->synthesize($synth, $text, $speed, $speakerId);
        } finally {
            $this->piper->piper_free($synth);
        }
    }

    private function synthesize(FFI\CData $synth, string $text, float $speed, int $speakerId): string
    {
        // Get default options and apply overrides
        $options = $this->piper->piper_default_synthesize_options($synth);
        $options->speaker_id = $speakerId;

        if ($speed > 0.0 && $speed !== 1.0) {
            $options->length_scale = 1.0 / $speed;
        }

        // Start synthesis
        $rc = $this->piper->piper_synthesize_start($synth, $text, FFI::addr($options));
        if ($rc !== 0) {
            throw new PiperException("piper_synthesize_start failed (rc={$rc})");
        }

        // Collect audio chunks
        $pcmData = '';
        $sampleRate = 0;
        $chunk = $this->piper->new('piper_audio_chunk');

        while (true) {
            $rc = $this->piper->piper_synthesize_next($synth, FFI::addr($chunk));

            if ($rc === -1) {
                throw new PiperException('piper_synthesize_next failed');
            }

            $sampleRate = $chunk->sample_rate;
            $numSamples = $chunk->num_samples;

            for ($i = 0; $i < $numSamples; $i++) {
                $sample = $chunk->samples[$i];
                if ($sample > 1.0) {
                    $sample = 1.0;
                } elseif ($sample < -1.0) {
                    $sample = -1.0;
                }
                $pcmData .= pack('v', ((int)($sample * 32767)) & 0xFFFF);
            }

            if ($rc === 1) { // PIPER_DONE
                break;
            }
        }

        return $this->buildWav($pcmData, $sampleRate);
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

    private function findLibpiper(): string
    {
        $candidates = [
            $this->modelsPath . '/../lib/libpiper.so',
            $this->modelsPath . '/../libpiper.so',
            '/usr/lib/libpiper.so',
            '/usr/local/lib/libpiper.so',
            '/opt/piper/libpiper.so',
            '/opt/piper/lib/libpiper.so',
        ];

        foreach ($candidates as $path) {
            $real = realpath($path);
            if ($real !== false && is_file($real)) {
                return $real;
            }
        }

        throw new PiperException(
            "libpiper.so not found. Searched:\n  " . implode("\n  ", $candidates)
            . "\nPass libpiperPath to the constructor or build libpiper and place it in one of these locations."
        );
    }

    private function findOnnxrt(string $libpiperDir): string
    {
        $candidates = [
            $libpiperDir . '/libonnxruntime.so',
            $libpiperDir . '/lib/libonnxruntime.so',
            '/usr/lib/x86_64-linux-gnu/libonnxruntime.so',
            '/usr/lib/libonnxruntime.so',
            '/usr/local/lib/libonnxruntime.so',
        ];

        foreach ($candidates as $path) {
            $real = realpath($path);
            if ($real !== false && is_file($real)) {
                return $real;
            }
        }

        throw new PiperException(
            "libonnxruntime.so not found. Searched:\n  " . implode("\n  ", $candidates)
            . "\nPass onnxrtPath to the constructor."
        );
    }

    private function findEspeakData(string $libpiperDir): string
    {
        $candidates = [
            $libpiperDir . '/espeak-ng-data',
            $libpiperDir . '/../espeak-ng-data',
            '/usr/share/espeak-ng-data',
            '/usr/lib/espeak-ng-data',
        ];

        foreach ($candidates as $path) {
            $real = realpath($path);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }

        throw new PiperException(
            "espeak-ng-data directory not found. Searched:\n  " . implode("\n  ", $candidates)
            . "\nPass espeakDataPath to the constructor."
        );
    }
}
