<?php

/**
 * Text-to-speech with warm-up: avoids first-chunk delay.
 *
 * The first inference in ONNX Runtime is slow due to initialization.
 * This example shows how to warm up the model for production use.
 *
 * Usage:
 *   php examples/warmup.php
 *   php examples/warmup.php "Custom text"
 *   php examples/warmup.php "Cześć!" pl_PL-gosia-medium
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

$text  = $argv[1] ?? 'Hello! This is Piper text to speech with warm-up.';
$voice = $argv[2] ?? 'en_US-lessac-medium';
$output = __DIR__ . '/../output.wav';

echo "Voice: {$voice}\n";
echo "Text:  {$text}\n\n";

// Measure model loading
$t0 = microtime(true);
$model = $piper->loadModel($voice);
$loadTime = round((microtime(true) - $t0) * 1000);
echo "Model loaded in {$loadTime}ms\n";

// Warm-up: initialize ONNX Runtime to avoid first-chunk delay
echo "Warming up... ";
$warmupTime = $model->warmUp();
echo "done in {$warmupTime}ms\n\n";

// Now the real synthesis will be fast
$t0 = microtime(true);
$wav = $model->speak($text);
$genTime = round((microtime(true) - $t0) * 1000);

file_put_contents($output, $wav);

$duration = round((strlen($wav) - 44) / 2 / 22050, 2);
echo "Generated {$duration}s audio in {$genTime}ms (after warm-up)\n";
echo "Saved: {$output}\n";
echo "Play:  aplay {$output}\n";
