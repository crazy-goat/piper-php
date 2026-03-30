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
