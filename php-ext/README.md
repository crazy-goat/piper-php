# ONNX TTS PHP Extension

PHP extension (C) for Text-to-Speech using ONNX Runtime.

## Requirements

- PHP 8.1+ with development headers (php-dev)
- ONNX Runtime library (libonnxruntime-dev)
- C compiler (gcc)
- phpize tool

## Installation

### 1. Install dependencies

```bash
sudo apt-get install php-dev libonnxruntime-dev build-essential
```

### 2. Build the extension

```bash
cd php-ext
phpize
./configure --with-onnx_tts
make
sudo make install
```

### 3. Enable the extension

Add to your php.ini:
```ini
extension=onnx_tts.so
```

Or create a separate config file:
```bash
echo "extension=onnx_tts.so" | sudo tee /etc/php/8.1/mods-available/onnx_tts.ini
sudo phpenmod onnx_tts
```

### 4. Verify installation

```bash
php -m | grep onnx_tts
php -r "echo onnx_tts_version();"
```

## Usage

```php
<?php
// Get ONNX Runtime version
echo onnx_tts_version(); // e.g., "1.21.0"

// More functions coming soon...
?>
```

## Troubleshooting

### "phpize: command not found"
Install PHP development package:
```bash
sudo apt-get install php-dev
```

### "onnxruntime_c_api.h: No such file or directory"
Install ONNX Runtime development package:
```bash
sudo apt-get install libonnxruntime-dev
```

### "cannot find -lonnxruntime"
Make sure libonnxruntime.so is in standard library path:
```bash
sudo ldconfig
# or specify path:
./configure --with-onnx_tts=/usr/lib/x86_64-linux-gnu
```

## Development

To rebuild after changes:
```bash
cd php-ext
make clean
make
sudo make install
```

## License

MIT
