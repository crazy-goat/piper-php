<?php

/**
 * Text-to-speech with auto warm-up on model load.
 *
 * This is the simplest API for production use - warm-up happens automatically.
 *
 * Usage:
 *   php examples/autowarmup.php
 *   php examples/autowarmup.php "Custom text"
 *   php examples/autowarmup.php "Cześć!" pl_PL-gosia-medium
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\PiperTTS\PiperTTS;

$piper = new PiperTTS(
    modelsPath:     __DIR__ . '/../models',
    libpiperPath:   __DIR__ . '/../piper1-gpl/libpiper/build/libpiper.so',
    onnxrtPath:     __DIR__ . '/../piper1-gpl/libpiper/build/piper1-gpl/libpiper/install/lib/libonnxruntime.so',
    espeakDataPath: __DIR__ . '/../piper1-gpl/libpiper/build/piper1-gpl/libpiper/install/espeak-ng-data',
);

$text  = $argv[1] ?? 'Hello! This is Piper with automatic warm-up.';
$voice = $argv[2] ?? 'en_US-lessac-medium';
$output = __DIR__ . '/../output.wav';

echo "Voice: {$voice}\n";
echo "Text:  {$text}\n\n";

// Load model with automatic warm-up (no first-chunk delay!)
$t0 = microtime(true);
$model = $piper->loadModel($voice, warmUp: true);
$totalTime = round((microtime(true) - $t0) * 1000);
echo "Model loaded and warmed up in {$totalTime}ms\n";

// Now synthesis is fast from the first call
$t0 = microtime(true);
$wav = $model->speak($text);
$genTime = round((microtime(true) - $t0) * 1000);

file_put_contents($output, $wav);

$duration = round((strlen($wav) - 44) / 2 / 22050, 2);
echo "Generated {$duration}s audio in {$genTime}ms\n";
echo "Saved: {$output}\n";
echo "Play:  aplay {$output}\n";
