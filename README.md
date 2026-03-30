# decodo/piper-tts

Text-to-speech in PHP using [Piper](https://github.com/OHF-Voice/piper1-gpl) via FFI.

## Requirements

- PHP 8.1+ with FFI extension
- Linux x86_64
- libpiper built from source

## Building libpiper

```bash
git clone https://github.com/OHF-Voice/piper1-gpl.git
cd piper1-gpl/libpiper
mkdir -p build && cd build
cmake .. -DCMAKE_INSTALL_PREFIX=../install
make -j$(nproc)
make install
```

This produces:
- `install/libpiper.so`
- `install/lib/libonnxruntime.so`
- `install/espeak-ng-data/`

## Installation

```bash
composer require decodo/piper-tts
```

## Download a voice

```bash
# List available voices
vendor/bin/piper-tts list
vendor/bin/piper-tts list --language=pl

# Download a voice
vendor/bin/piper-tts download en_US-lessac-medium ./models

# See what you have locally
vendor/bin/piper-tts installed ./models
```

## Usage

```php
use Decodo\PiperTTS\PiperTTS;

$piper = new PiperTTS('./models');

// List installed voices
foreach ($piper->voices() as $voice) {
    echo "{$voice->key} — {$voice->language}, {$voice->quality}\n";
}

// Synthesize text to WAV
$wav = $piper->speak('Hello world!', 'en_US-lessac-medium');
file_put_contents('output.wav', $wav);

// Adjust speed (2.0 = twice as fast)
$wav = $piper->speak('Fast speech', 'en_US-lessac-medium', speed: 2.0);
```

If libpiper is not in a standard location, pass the paths explicitly:

```php
$piper = new PiperTTS(
    modelsPath:     './models',
    libpiperPath:   '/opt/piper/libpiper.so',
    onnxrtPath:     '/opt/piper/lib/libonnxruntime.so',
    espeakDataPath: '/opt/piper/espeak-ng-data',
);
```

## License

MIT (this package). Piper itself is GPL-3.0.
