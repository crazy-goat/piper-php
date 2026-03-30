<?php

/**
 * Simple text-to-speech: text → WAV file.
 *
 * Usage:
 *   php examples/speak.php
 *   php examples/speak.php "Custom text"
 *   php examples/speak.php "Cześć!" pl_PL-gosia-medium
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\PiperTTS\PiperTTS;

$piper = new PiperTTS(modelsPath: __DIR__ . '/../models');

$text  = $argv[1] ?? 'Hello! This is Piper text to speech, running natively in PHP.';
$voice = $argv[2] ?? 'en_US-lessac-medium';
$output = __DIR__ . '/speak.wav';

echo "Voice: {$voice}\n";
echo "Text:  {$text}\n\n";

// Measure model loading
$t0 = microtime(true);
$model = $piper->loadModel($voice);
$loadTime = round((microtime(true) - $t0) * 1000);
echo "Model loaded in {$loadTime}ms\n";

// Measure generation
$t0 = microtime(true);
$wav = $model->speak($text);
$genTime = round((microtime(true) - $t0) * 1000);

file_put_contents($output, $wav);

$duration = round((strlen($wav) - 44) / 2 / 22050, 2);
echo "Generated {$duration}s audio in {$genTime}ms\n";
echo "Saved: {$output}\n";
echo "Play:  aplay {$output}\n";
