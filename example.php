<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Decodo\PiperTTS\PiperTTS;

// --- Config (adjust paths to your setup) ---
$modelsPath     = __DIR__ . '/models';
$libpiperPath   = __DIR__ . '/piper1-gpl/libpiper/install/libpiper.so';
$onnxrtPath     = __DIR__ . '/piper1-gpl/libpiper/install/lib/libonnxruntime.so';
$espeakDataPath = __DIR__ . '/piper1-gpl/libpiper/install/espeak-ng-data';

$voice = $argv[1] ?? 'en_US-lessac-medium';
$text  = $argv[2] ?? 'Hello! This is Piper text to speech, running natively in PHP.';

// --- Init ---
$piper = new PiperTTS($modelsPath, $libpiperPath, $onnxrtPath, $espeakDataPath);

// --- Show installed voices ---
echo "Installed voices:\n";
foreach ($piper->voices() as $v) {
    echo "  {$v->key}  ({$v->language}, {$v->quality})\n";
}
echo "\n";

// --- Synthesize ---
echo "Voice: {$voice}\n";
echo "Text:  {$text}\n";

$t0 = microtime(true);
$wav = $piper->speak($text, $voice);
$elapsed = round(microtime(true) - $t0, 2);

$outputFile = __DIR__ . '/output.wav';
file_put_contents($outputFile, $wav);

$size = round(strlen($wav) / 1024, 1);
$duration = round((strlen($wav) - 44) / 2 / 22050, 2);

echo "Generated {$duration}s audio in {$elapsed}s ({$size} KB)\n";
echo "Saved: {$outputFile}\n";
echo "Play:  aplay {$outputFile}\n";
