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

use Decodo\PiperTTS\PiperTTS;

$piper = new PiperTTS(
    modelsPath:     __DIR__ . '/../models',
    libpiperPath:   __DIR__ . '/../piper1-gpl/libpiper/install/libpiper.so',
    onnxrtPath:     __DIR__ . '/../piper1-gpl/libpiper/install/lib/libonnxruntime.so',
    espeakDataPath: __DIR__ . '/../piper1-gpl/libpiper/install/espeak-ng-data',
);

$text  = $argv[1] ?? 'This is the first sentence. Here comes the second one. And finally, the third sentence!';
$voice = $argv[2] ?? 'en_US-lessac-medium';
$output = __DIR__ . '/../output.wav';

echo "Voice: {$voice}\n";
echo "Text:  {$text}\n\n";

// Stream chunks — each chunk is one sentence of audio
$allPcm = '';
$sampleRate = 0;
$chunkNum = 0;
$t0 = microtime(true);

foreach ($piper->speakStreaming($text, $voice) as $chunk) {
    $chunkNum++;
    $sampleRate = $chunk->sampleRate;
    $ms = $sampleRate > 0 ? round(strlen($chunk->pcmData) / 2 / $sampleRate * 1000) : 0;
    $elapsed = round((microtime(true) - $t0) * 1000);

    printf(
        "  Chunk %d: %5d bytes, %4dms audio, at %dms %s\n",
        $chunkNum,
        strlen($chunk->pcmData),
        $ms,
        $elapsed,
        $chunk->isLast ? '(last)' : '',
    );

    $allPcm .= $chunk->pcmData;
}

$totalElapsed = round(microtime(true) - $t0, 2);
$duration = $sampleRate > 0 ? round(strlen($allPcm) / 2 / $sampleRate, 2) : 0;

echo "\nTotal: {$duration}s audio in {$chunkNum} chunks, synthesized in {$totalElapsed}s\n";

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
