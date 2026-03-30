# ONNX PHP TTS Library Design

**Date:** 2025-03-30  
**Status:** Approved  
**Scope:** PHP library for Text-to-Speech using ONNX models via FFI

---

## 1. Overview

Library providing TTS (Text-to-Speech) capabilities in PHP through ONNX Runtime FFI bindings. Supports multiple TTS models (Piper, Coqui, MeloTTS) with fluent API, audio compression, and model management.

### Success Criteria
- Load and run ONNX TTS models from PHP via FFI
- Support multiple model formats (Piper, Coqui, MeloTTS)
- Generate audio faster-than-real-time (2-3x speedup)
- Output formats: WAV + MP3 (with optional compression)
- Auto-download models from HuggingFace
- Fluent API for ease of use
- PHP 8.1+ compatibility

---

## 2. Architecture

### 2.1 Layer Structure

```
┌─────────────────────────────────────────┐
│           PHP User Code                 │
│    $tts->model('piper')->speak($text)   │
├─────────────────────────────────────────┤
│           PHP FFI Layer                 │
│    OnnxRuntime.php (C API wrapper)      │
│    TTSModel.php (model abstraction)     │
│    AudioProcessor.php (compression)     │
├─────────────────────────────────────────┤
│           Shared Libraries              │
│    libonnxruntime.so (HuggingFace)      │
│    libmp3lame.so (optional)             │
├─────────────────────────────────────────┤
│           ONNX Models                   │
│    *.onnx (TTS models)                  │
│    config.json (model metadata)         │
└─────────────────────────────────────────┘
```

### 2.2 Design Decisions

- **Thin FFI Wrapper**: Direct ORT C API calls, no custom C code
- **Model Abstraction**: PHP classes handle model-specific logic (tokenization, preprocessing)
- **Audio Processing**: PHP for WAV headers, FFI for MP3 compression (libmp3lame)
- **No C Extension**: FFI-only approach for easier distribution

---

## 3. Core Components

### 3.1 OnnxRuntime

Wrapper for ONNX Runtime C API.

**Responsibilities:**
- Load libonnxruntime.so via FFI
- Create inference sessions
- Run inference with input/output tensors
- Memory management (cleanup)

**Public API:**
```php
class OnnxRuntime {
    public function __construct(string $libraryPath);
    public function createSession(string $modelPath): OrtSession;
    public function getVersion(): string;
    public function getAvailableProviders(): array;
}
```

### 3.2 OrtSession

Represents ONNX model session.

**Responsibilities:**
- Manage ORT session lifecycle
- Query input/output metadata
- Execute inference

**Public API:**
```php
class OrtSession {
    public function getInputNames(): array;
    public function getOutputNames(): array;
    public function getInputShape(string $name): array;
    public function run(array $inputs): array;
    public function close(): void;
}
```

### 3.3 TTSModel (Abstract)

Base class for TTS model implementations.

**Responsibilities:**
- Load model configuration
- Preprocess text for inference
- Run inference pipeline
- Postprocess audio output

**Public API:**
```php
abstract class TTSModel {
    public function __construct(OnnxRuntime $runtime, string $modelDir);
    public function load(): void;
    abstract public function synthesize(string $text): AudioBuffer;
    public function getSampleRate(): int;
    public function getSpeakers(): array;
    public function getLanguages(): array;
}
```

### 3.4 Model Implementations

**PiperModel** - Piper TTS support
- Tokenization: character-level
- Input: text → input_ids
- Output: mel spectrogram → vocoder → PCM
- Config: phoneme language, speaker mapping

**CoquiModel** - Coqui TTS support
- Tokenization: phoneme-based
- Input: phonemes → input_ids
- Output: raw audio or mel → vocoder
- Config: multi-speaker support

**MeloTTSModel** - MeloTTS support
- Tokenization: language-specific
- Input: text/accent tokens
- Output: audio features
- Config: language codes, speed control

### 3.5 AudioBuffer

Container for audio data with format conversion.

**Responsibilities:**
- Store raw PCM audio (float32)
- Convert to various formats
- Apply compression

**Public API:**
```php
class AudioBuffer {
    public static function fromFloatArray(array $data, int $sampleRate): self;
    public function getSampleRate(): int;
    public function getDuration(): float;
    public function toWav(): string;
    public function toMp3(int $bitrate = 192): string;
    public function toOgg(int $quality = 5): string;
    public function save(string $path, string $format = 'wav'): void;
}
```

### 3.6 TextPreprocessor

Text normalization for TTS input.

**Responsibilities:**
- Normalize numbers (123 → "sto dwadzieścia trzy")
- Expand abbreviations ("dr" → "doktor")
- Handle punctuation
- Language-specific rules

**Public API:**
```php
class TextPreprocessor {
    public function __construct(string $language = 'pl');
    public function normalize(string $text): string;
    public function addRule(string $pattern, string $replacement): void;
}
```

### 3.7 ModelManager

Download and cache management for TTS models.

**Responsibilities:**
- List available models in cache
- Download from HuggingFace
- Verify model integrity
- Version management

**Public API:**
```php
class ModelManager {
    public function __construct(string $cacheDir);
    public function listAvailable(): array;
    public function listRemote(): array;
    public function download(string $modelId, string $source = 'huggingface'): void;
    public function getPath(string $modelId): string;
    public function isDownloaded(string $modelId): bool;
    public function delete(string $modelId): void;
}
```

### 3.8 TTS (Main API)

Fluent API entry point for users.

**Responsibilities:**
- Provide chainable configuration
- Coordinate components
- Generate audio

**Public API:**
```php
class TTS {
    public function __construct(OnnxRuntime $runtime, ModelManager $manager);
    public function model(string $modelId): self;
    public function speaker(string $speakerId): self;
    public function speed(float $factor): self;
    public function language(string $lang): self;
    public function speak(string $text): AudioBuffer;
    public function speakStream(string $text): Generator;
    public function save(string $path, string $format = 'wav'): void;
}
```

---

## 4. Data Flow

### 4.1 Synthesis Flow

```
User: $tts->model('piper')->speak('Witaj świecie')

1. TTS::model('piper')
   └── ModelManager::getPath('piper')
       └── Check cache: ~/.cache/onnx-tts/piper/
           └── If missing → download from HuggingFace

2. TTS::speak('Witaj świecie')
   └── TextPreprocessor::normalize('Witaj świecie')
       └── 'Witaj świecie' (basic normalization)
   
   └── PiperModel::synthesize('Witaj świecie')
       └── Text → input_ids (tokenization)
       └── ONNX inference (input_ids → mel spectrogram)
       └── Vocoder inference (mel → PCM float)
       └── AudioBuffer::fromFloatArray($pcm, 22050)

3. AudioBuffer::toWav()
   └── Add WAV header (RIFF, fmt, data)
   └── Return binary string

4. (Optional) AudioBuffer::toMp3(192)
   └── FFI call to libmp3lame
   └── Return MP3 binary
```

### 4.2 FFI Mapping

**ONNX Runtime C API:**
```c
// Core types
typedef void* OrtEnv;
typedef void* OrtSession;
typedef void* OrtValue;
typedef void* OrtStatus;

// Session creation
OrtStatus* OrtCreateSession(OrtEnv* env, const char* model_path, 
                            const OrtSessionOptions* options, 
                            OrtSession** session);

// Inference
OrtStatus* OrtRun(OrtSession* session, const OrtRunOptions* run_options,
                    const char* const* input_names, const OrtValue* const* inputs,
                    size_t input_count, const char* const* output_names,
                    size_t output_count, OrtValue** outputs);

// Memory management
void OrtReleaseSession(OrtSession* session);
void OrtReleaseValue(OrtValue* value);
void OrtReleaseStatus(OrtStatus* status);
```

**FFI Definition in PHP:**
```php
$ffi = FFI::cdef("
    typedef void* OrtEnv;
    typedef void* OrtSession;
    typedef void* OrtValue;
    typedef void* OrtStatus;
    
    OrtStatus* OrtCreateSession(OrtEnv* env, const char* model_path, 
                                void* options, OrtSession** session);
    OrtStatus* OrtRun(OrtSession* session, void* run_options,
                      const char** input_names, OrtValue** inputs,
                      size_t input_count, const char** output_names,
                      size_t output_count, OrtValue** outputs);
    void OrtReleaseSession(OrtSession* session);
    void OrtReleaseValue(OrtValue* value);
    const char* OrtGetErrorMessage(OrtStatus* status);
    void OrtReleaseStatus(OrtStatus* status);
", "libonnxruntime.so");
```

---

## 5. Error Handling

### 5.1 Exception Hierarchy

```
OnnxTTSException (base)
├── OnnxRuntimeException
│   ├── LibraryNotFoundException
│   ├── SessionException
│   └── InferenceException
├── ModelException
│   ├── ModelNotFoundException
│   ├── ModelCorruptedException
│   └── UnsupportedModelException
├── AudioException
│   ├── CompressionException
│   └── InvalidFormatException
└── NetworkException
```

### 5.2 Error Scenarios

**FFI/ORT Errors:**
- Library not found → `LibraryNotFoundException` with installation instructions
- Session creation failed → `SessionException` with model path
- Inference error → `InferenceException` with input shapes

**Model Errors:**
- Model not in cache → `ModelNotFoundException` with download suggestion
- Corrupted ONNX file → `ModelCorruptedException` with verification
- Unknown model format → `UnsupportedModelException`

**Audio Errors:**
- Compression library missing → `CompressionException` with fallback to WAV
- Invalid format requested → `InvalidFormatException`

**Network Errors:**
- Download failed → `NetworkException` with retry suggestion
- Checksum mismatch → `ModelCorruptedException`

### 5.3 Error Recovery

- **Graceful degradation**: If MP3 compression fails, fallback to WAV
- **Retry logic**: Network downloads with exponential backoff
- **Clear messages**: Actionable error messages with next steps

---

## 6. Testing Strategy

### 6.1 Test Levels

**Unit Tests** (mocked, no ORT):
- `TextPreprocessorTest` - normalization rules
- `AudioBufferTest` - format conversions
- `ModelManagerTest` - filesystem operations

**Integration Tests** (with ORT):
- `OnnxRuntimeTest` - library loading, version check
- `OrtSessionTest` - session lifecycle
- `TTSModelTest` - with tiny test model

**Functional Tests** (end-to-end):
- `TTSTest` - full synthesis pipeline
- `CompressionTest` - audio format conversion
- `DownloadTest` - model fetching

### 6.2 Test Fixtures

- `tests/fixtures/tiny_model.onnx` - 1MB test model
- `tests/fixtures/tiny_config.json` - model configuration
- `tests/fixtures/sample_texts/` - multilingual test texts

### 6.3 CI/CD

- GitHub Actions matrix: PHP 8.1, 8.2, 8.3
- Pre-built ORT binaries for Linux x64
- Tests skip if FFI unavailable (`@requires extension ffi`)

---

## 7. Dependencies

### 7.1 System Requirements

- PHP 8.1+ with FFI extension
- ONNX Runtime 1.16+ (libonnxruntime.so)
- Optional: libmp3lame for MP3 compression

### 7.2 PHP Extensions

- `ffi` (required)
- `mbstring` (UTF-8 support)
- `json` (config parsing)

### 7.3 External Libraries

**ONNX Runtime:**
- Download from GitHub releases
- Platforms: Linux x64, macOS, Windows
- Installation script provided

**Compression (optional):**
- libmp3lame for MP3
- libvorbis for OGG

---

## 8. Directory Structure

```
onnx-php-tts/
├── src/
│   ├── OnnxRuntime.php
│   ├── OrtSession.php
│   ├── TTSModel.php
│   ├── AudioBuffer.php
│   ├── TextPreprocessor.php
│   ├── ModelManager.php
│   ├── TTS.php
│   ├── Models/
│   │   ├── PiperModel.php
│   │   ├── CoquiModel.php
│   │   └── MeloTTSModel.php
│   └── Exception/
│       ├── OnnxTTSException.php
│       ├── OnnxRuntimeException.php
│       ├── ModelException.php
│       └── AudioException.php
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── fixtures/
├── scripts/
│   └── install-ort.sh
├── docs/
│   └── usage.md
├── composer.json
└── README.md
```

---

## 9. Performance Targets

- **Inference speed**: 2-3x faster than real-time
- **Model loading**: < 2s for 100MB model
- **Memory usage**: < 500MB peak for standard models
- **Streaming latency**: < 100ms first chunk

---

## 10. Future Enhancements

- GPU support (CUDA, DirectML providers)
- Additional models (Bark, Tortoise, etc.)
- SSML support for advanced control
- Real-time streaming WebSocket API
- Voice cloning interface

---

## 11. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| FFI performance overhead | Medium | Benchmark early, optimize hot paths |
| ORT binary compatibility | High | Test on multiple platforms, provide install script |
| Large model downloads | Medium | Progress bars, resume support, CDN mirrors |
| Memory leaks in FFI | High | Strict cleanup, use RAII patterns, valgrind tests |
| Model format changes | Low | Abstract model interface, adapter pattern |

---

**Approved by:** User  
**Next Step:** Implementation plan via writing-plans skill
