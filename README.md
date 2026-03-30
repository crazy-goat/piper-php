# ONNX PHP TTS

PHP library for Text-to-Speech using ONNX models via FFI.

## Features

- Load and run ONNX TTS models (Piper, Coqui, MeloTTS)
- Simple fluent API
- Audio export (WAV, MP3, OGG)
- Model auto-download from HuggingFace
- Multi-language support

## Requirements

- PHP 8.1+
- FFI extension enabled
- ONNX Runtime 1.16+
- mbstring, json extensions

## Installation

```bash
composer require decodo/onnx-tts
```

Install ONNX Runtime:
```bash
./vendor/bin/install-ort.sh
# Or manually download from https://github.com/microsoft/onnxruntime/releases
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use OnnxTTS\OnnxRuntime;
use OnnxTTS\ModelManager;
use OnnxTTS\TTS;

// Initialize
$runtime = new OnnxRuntime('/lib/x86_64-linux-gnu/libonnxruntime.so.1.21');
$manager = new ModelManager(getenv('HOME') . '/.cache/onnx-tts');
$tts = new TTS($runtime, $manager);

// Download a model (first time only)
$manager->download('piper-pl');

// Generate speech
$audio = $tts
    ->model('piper-pl')
    ->speed(1.2)
    ->speak('Witaj świecie!');

// Save to file
$audio->save('output.wav', 'wav');
```

## API Reference

### TTS Class

```php
$tts = new TTS($runtime, $manager);

// Fluent configuration
$audio = $tts
    ->model('model-id')           // Select model
    ->speaker('speaker-id')       // For multi-speaker models
    ->speed(1.2)                  // 0.5 - 2.0
    ->language('pl')              // Language hint
    ->speak('Text to synthesize');

// Save audio
$audio->save('output.wav', 'wav');
$audio->save('output.mp3', 'mp3');
```

### ModelManager

```php
$manager = new ModelManager($cacheDir);

// List available models
$models = $manager->listAvailable();

// Download from HuggingFace
$manager->download('piper-pl', 'huggingface');

// Check if downloaded
if ($manager->isDownloaded('piper-pl')) {
    $path = $manager->getPath('piper-pl');
}
```

## Supported Models

### Piper
- Polish: `piper-pl`
- English: `piper-en`
- More at https://huggingface.co/rhasspy/piper-voices

### Coqui TTS
Coming soon...

### MeloTTS
Coming soon...

## Testing

```bash
./vendor/bin/phpunit
```

## License

MIT
