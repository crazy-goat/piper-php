# Piper PHP

Text-to-speech in PHP using [Piper](https://github.com/rhasspy/piper) via FFI.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Fast, local text-to-speech synthesis without external services. Piper runs entirely on your machine using ONNX Runtime.

## Features

- 🚀 **Fast** - Local synthesis, no network latency
- 🔊 **High quality** - Uses Piper neural TTS models
- 📦 **Easy installation** - Composer package with FFI
- 🎯 **Simple API** - Load model once, synthesize many times
- ⚡ **Warm-up support** - Avoid first-chunk delay in production
- 🔄 **Streaming** - Generate audio chunk by chunk for real-time playback

## Requirements

- PHP >= 8.1 with FFI extension enabled
- Piper library (libpiper.so) and ONNX Runtime
- Voice models (.onnx files)

## Installation

```bash
composer require crazy-goat/piper-php
```

### Download Pre-built Libraries

You can download pre-built libraries from the [latest release](https://github.com/crazy-goat/piper-php/releases/latest):

```bash
# Download and extract pre-built libraries
wget https://github.com/crazy-goat/piper-php/releases/latest/download/piper-build-linux-x86_64.tar.gz
tar -xzf piper-build-linux-x86_64.tar.gz -C ./piper-libs
```

This archive contains:
- `libpiper.so` - Piper library
- `libonnxruntime.so` - ONNX Runtime
- `espeak-ng-data/` - Phoneme data

### Building from Source

If you prefer to build from source or need a different architecture:

```bash
# Clone with submodules
git clone --recursive https://github.com/crazy-goat/piper-php.git
cd piper-php

# Or initialize submodules if already cloned
git submodule update --init --recursive

# Build libpiper and dependencies
make build-piper1
```

The build will create:
- `piper1-gpl/libpiper/build/libpiper.so` - Piper library
- `piper1-gpl/libpiper/build/piper1-gpl/libpiper/install/lib/libonnxruntime.so` - ONNX Runtime
- `piper1-gpl/libpiper/build/piper1-gpl/libpiper/install/espeak-ng-data` - Phoneme data

### Downloading Voice Models

Use the included CLI tool to download voices from HuggingFace:

```bash
# List available voices
./bin/piper-tts list

# Filter by language
./bin/piper-tts list --language=pl

# Download a voice
./bin/piper-tts download en_US-lessac-medium ./models

# List installed voices
./bin/piper-tts installed ./models
```

Or download manually from [HuggingFace](https://huggingface.co/rhasspy/piper-voices/tree/main).

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use CrazyGoat\PiperTTS\PiperTTS;

// Initialize TTS with pre-built libraries (downloaded from release)
$piper = new PiperTTS(
    modelsPath:     __DIR__ . '/models',
    libpiperPath:   __DIR__ . '/piper-libs/libpiper.so',
    onnxrtPath:     __DIR__ . '/piper-libs/libonnxruntime.so',
    espeakDataPath: __DIR__ . '/piper-libs/espeak-ng-data',
);

// Load voice with automatic warm-up (recommended for production)
$model = $piper->loadModel('en_US-lessac-medium', warmUp: true);

// Synthesize text to WAV
$wav = $model->speak('Hello! This is Piper text to speech in PHP.');
file_put_contents('output.wav', $wav);
```

## API Reference

### PiperTTS

Main factory class for loading models.

```php
$piper = new PiperTTS(
    modelsPath:     '/path/to/models',           // Directory with .onnx files
    libpiperPath:   '/path/to/libpiper.so',      // Optional: auto-detected if null
    onnxrtPath:     '/path/to/libonnxruntime.so', // Optional: auto-detected if null
    espeakDataPath: '/path/to/espeak-ng-data',    // Optional: auto-detected if null
);
```

#### Methods

- `loadModel(string $voice, bool $warmUp = false): LoadedModel` - Load a voice model
- `voices(): VoiceInfo[]` - List available voices in models directory

### LoadedModel

Represents a loaded voice model. Reuse this instance for multiple synthesis calls.

#### Methods

- `speak(string $text, float $speed = 1.0, int $speakerId = 0): string` - Synthesize to WAV
- `speakStreaming(string $text, float $speed = 1.0, int $speakerId = 0): \Generator` - Stream chunks
- `warmUp(): int` - Warm up the model, returns time in milliseconds
- `free(): void` - Explicitly free resources (called automatically in destructor)

### Speed Control

Adjust speech speed with the `speed` parameter:

```php
// 2x faster
$fast = $model->speak('Hello world', speed: 2.0);

// 0.5x slower (half speed)
$slow = $model->speak('Hello world', speed: 0.5);
```

### Streaming

For real-time applications, use streaming to get audio chunks as they're generated:

```php
foreach ($model->speakStreaming('First sentence. Second sentence.') as $chunk) {
    // $chunk->pcmData - Raw 16-bit PCM audio
    // $chunk->sampleRate - Sample rate (e.g., 22050)
    // $chunk->isLast - True if this is the final chunk
    
    // Send to audio player, WebSocket, etc.
    $player->play($chunk->pcmData);
}
```

## Production Tips

### Warm-up

The first inference in ONNX Runtime is slow (~900ms) due to initialization. Use warm-up to avoid this delay:

```php
// Option 1: Auto warm-up on load (recommended)
$model = $piper->loadModel('voice', warmUp: true);

// Option 2: Manual warm-up with timing
$model = $piper->loadModel('voice');
$ms = $model->warmUp();
echo "Warmed up in {$ms}ms";
```

### Reuse Models

Load the model once and reuse for multiple synthesis calls:

```php
// Good: Load once, use many times
$model = $piper->loadModel('en_US-lessac-medium', warmUp: true);

foreach ($texts as $text) {
    $wav = $model->speak($text);
    // ...
}

// Bad: Loading model on every request (slow!)
foreach ($texts as $text) {
    $model = $piper->loadModel('en_US-lessac-medium');  // Don't do this
    $wav = $model->speak($text);
}
```

### Long-Running Processes

For daemons or workers, load models at startup:

```php
// At application startup
$piper = new PiperTTS('/path/to/models');
$voiceCache = [];

function getVoice($piper, $voiceKey) {
    global $voiceCache;
    if (!isset($voiceCache[$voiceKey])) {
        $voiceCache[$voiceKey] = $piper->loadModel($voiceKey, warmUp: true);
    }
    return $voiceCache[$voiceKey];
}

// In request handler
$model = getVoice($piper, 'en_US-lessac-medium');
$wav = $model->speak($userText);
```

## Examples

See the `examples/` directory:

- `speak.php` - Basic text-to-speech with timing
- `stream.php` - Streaming synthesis with per-chunk timing
- `warmup.php` - Manual warm-up demonstration
- `autowarmup.php` - Automatic warm-up on model load

Run examples:

```bash
php examples/speak.php "Hello world"
php examples/stream.php "First sentence. Second sentence."
php examples/autowarmup.php "Test text"
```

## Performance

Typical performance on modern CPU:

| Operation | Time |
|-----------|------|
| Model loading | ~900ms |
| Warm-up | ~70ms |
| Synthesis (after warm-up) | ~70-150ms per sentence |
| First chunk (without warm-up) | ~900ms |
| First chunk (with warm-up) | ~70ms |

## Troubleshooting

### FFI extension not found

Enable FFI in your `php.ini`:

```ini
extension=ffi
```

### Library not found errors

Specify full paths to libraries in the PiperTTS constructor:

```php
$piper = new PiperTTS(
    modelsPath:     '/full/path/to/models',
    libpiperPath:   '/full/path/to/libpiper.so',
    onnxrtPath:     '/full/path/to/libonnxruntime.so',
    espeakDataPath: '/full/path/to/espeak-ng-data',
);
```

### ONNX Runtime errors

Make sure all library dependencies are available:

```bash
# Check library dependencies
ldd /path/to/libonnxruntime.so
ldd /path/to/libpiper.so
```

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

- [Piper](https://github.com/rhasspy/piper) - Fast, local neural text-to-speech
- [ONNX Runtime](https://onnxruntime.ai/) - Cross-platform ML inference
- [espeak-ng](https://github.com/espeak-ng/espeak-ng) - Text-to-phoneme conversion

## Contributing

Contributions welcome! Please submit issues and pull requests on GitHub.
