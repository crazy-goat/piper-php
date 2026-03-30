<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS;

use CrazyGoat\PiperTTS\Exception\PiperException;
use FFI;

/**
 * Factory class for loading Piper TTS models.
 *
 * Manages library paths and creates LoadedModel instances.
 */
final readonly class PiperTTS
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
        private string $modelsPath,
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

        // Load onnxruntime with RTLD_GLOBAL so libpiper can find it.
        $dl = FFI::cdef('void *dlopen(const char *filename, int flags); char *dlerror(void);');
        $handle = $dl->dlopen($this->resolvedOnnxrtPath, 1 | 256); // RTLD_LAZY=1, RTLD_GLOBAL=256
        if ($handle === null || (is_object($handle) && FFI::isNull($handle))) {
            throw new PiperException('Failed to load onnxruntime: ' . FFI::string($dl->dlerror()));
        }

        $this->piper = FFI::cdef(self::CDEF, $libpiperPath);
    }

    /**
     * Load a voice model.
     *
     * @param string $voice   Voice key (e.g. "pl_PL-gosia-medium")
     * @param bool   $warmUp  Whether to warm up the model immediately (avoids first-chunk delay)
     */
    public function loadModel(string $voice, bool $warmUp = false): LoadedModel
    {
        $modelPath = $this->modelsPath . '/' . $voice . '.onnx';
        $configPath = $modelPath . '.json';

        if (!is_file($modelPath)) {
            throw new PiperException("Voice model not found: {$modelPath}");
        }
        if (!is_file($configPath)) {
            throw new PiperException("Voice config not found: {$configPath}");
        }

        $synth = $this->piper->piper_create($modelPath, $configPath, $this->resolvedEspeakDataPath);
        if (FFI::isNull($synth)) {
            throw new PiperException("piper_create failed for voice: {$voice}");
        }

        $model = new LoadedModel($this->piper, $synth);

        if ($warmUp) {
            $model->warmUp();
        }

        return $model;
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

        usort($voices, fn(VoiceInfo $a, VoiceInfo $b): int => $a->key <=> $b->key);

        return $voices;
    }

    private function findLibpiper(): string
    {
        // First check vendor directory (auto-downloaded libraries)
        $vendorPath = __DIR__ . '/../libs/libpiper.so';
        if (is_file($vendorPath)) {
            $real = realpath($vendorPath);
            if ($real !== false) {
                return $real;
            }
        }

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
            "libpiper.so not found. Searched:\n  " . implode("\n  ", array_merge([$vendorPath], $candidates))
            . "\nRun 'composer install' to download pre-built libraries, or pass libpiperPath to the constructor."
        );
    }

    private function findOnnxrt(string $libpiperDir): string
    {
        // First check vendor directory (auto-downloaded libraries)
        $vendorPath = __DIR__ . '/../libs/libonnxruntime.so';
        if (is_file($vendorPath)) {
            $real = realpath($vendorPath);
            if ($real !== false) {
                return $real;
            }
        }

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
            "libonnxruntime.so not found. Searched:\n  " . implode("\n  ", array_merge([$vendorPath], $candidates))
            . "\nRun 'composer install' to download pre-built libraries, or pass onnxrtPath to the constructor."
        );
    }

    private function findEspeakData(string $libpiperDir): string
    {
        // First check vendor directory (auto-downloaded libraries)
        $vendorPath = __DIR__ . '/../libs/espeak-ng-data';
        if (is_dir($vendorPath)) {
            $real = realpath($vendorPath);
            if ($real !== false) {
                return $real;
            }
        }

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
            "espeak-ng-data directory not found. Searched:\n  "
            . implode("\n  ", array_merge([$vendorPath], $candidates))
            . "\nRun 'composer install' to download pre-built libraries, or pass espeakDataPath to the constructor."
        );
    }
}
