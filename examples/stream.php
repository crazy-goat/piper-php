<?php

/**
 * Streaming text-to-speech: generates audio chunk by chunk.
 *
 * Piper splits text into sentences and yields each as a separate audio chunk.
 * This allows sending audio to a client before the entire text is synthesized.
 *
 * Usage:
 *   php examples/stream.php
 *   php examples/stream.php "First sentence. Second sentence. Third!"
 *   php examples/stream.php "Pierwsze zdanie. Drugie zdanie." pl_PL-gosia-medium
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\PiperTTS\PiperTTS;

$piper = new PiperTTS(
    modelsPath:     __DIR__ . '/../models',
    libpiperPath:   __DIR__ . '/../vendor/crazy-goat/piper-php/libs/libpiper.so',
    onnxrtPath:     __DIR__ . '/../vendor/crazy-goat/piper-php/libs/libonnxruntime.so',
    espeakDataPath: __DIR__ . '/../vendor/crazy-goat/piper-php/libs/espeak-ng-data',
);

$text  = $argv[1] ?? 'This is the first sentence. Here comes the second one. And finally, the third sentence!';
$voice = $argv[2] ?? 'en_US-lessac-medium';
$output = __DIR__ . '/../output.wav';

echo "Voice: {$voice}\n";
echo "Text:  {$text}\n\n";

// Measure model loading
$t0 = microtime(true);
$model = $piper->loadModel($voice);
$loadTime = round((microtime(true) - $t0) * 1000);
echo "Model loaded in {$loadTime}ms\n\n";

// Stream chunks — each chunk is one sentence of audio
$allPcm = '';
$sampleRate = 0;
$chunkNum = 0;
$totalGenTime = 0;
$lastChunkTime = microtime(true);

foreach ($model->speakStreaming($text) as $chunk) {
    $chunkNum++;
    $sampleRate = $chunk->sampleRate;
    $ms = $sampleRate > 0 ? round(strlen($chunk->pcmData) / 2 / $sampleRate * 1000) : 0;

    // Measure time since last chunk (generation time for this chunk)
    $now = microtime(true);
    $chunkGenTime = $chunkNum === 1 ? round(($now - $t0) * 1000) : round(($now - $lastChunkTime) * 1000);
    $lastChunkTime = $now;
    $totalGenTime += $chunkGenTime;

    printf(
        "  Chunk %d: %5d bytes, %4dms audio, generated in %3dms %s\n",
        $chunkNum,
        strlen($chunk->pcmData),
        $ms,
        $chunkGenTime,
        $chunk->isLast ? '(last)' : '',
    );

    $allPcm .= $chunk->pcmData;
}

$duration = $sampleRate > 0 ? round(strlen($allPcm) / 2 / $sampleRate, 2) : 0;

echo "\nTotal: {$duration}s audio in {$chunkNum} chunks, generated in {$totalGenTime}ms\n";

// Save combined WAV
$dataSize = strlen($allPcm);
$wav = 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
     . 'fmt ' . pack('V', 16) . pack('v', 1) . pack('v', 1)
     . pack('V', $sampleRate) . pack('V', $sampleRate * 2)
     . pack('v', 2) . pack('v', 16)
     . 'data' . pack('V', $dataSize) . $allPcm;

file_put_contents($output, $wav);
echo "Saved: {$output}\n";
echo "Play:  aplay {$output}\n";
