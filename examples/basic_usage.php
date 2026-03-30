<?php
/**
 * Basic usage example for ONNX PHP TTS
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OnnxTTS\OnnxRuntime;
use OnnxTTS\ModelManager;
use OnnxTTS\TTS;

// Configuration
$ortLibrary = getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21';
$cacheDir = getenv('HOME') . '/.cache/onnx-tts';

// Initialize components
echo "Initializing ONNX Runtime...\n";
$runtime = new OnnxRuntime($ortLibrary);
echo "ONNX Runtime version: " . $runtime->getVersion() . "\n";

echo "Setting up ModelManager...\n";
$manager = new ModelManager($cacheDir);

// Show available models
echo "\nAvailable models:\n";
foreach ($manager->listAvailable() as $model) {
    echo "  - {$model}\n";
}

echo "\nRemote models available for download:\n";
foreach ($manager->listRemote() as $id => $info) {
    echo "  - {$id} (from {$info['repo']})\n";
}

// Create TTS instance
$tts = new TTS($runtime, $manager);

// Example: Download and use a model
$modelId = 'piper-pl';

if (!$manager->isDownloaded($modelId)) {
    echo "\nDownloading model {$modelId}...\n";
    try {
        $manager->download($modelId);
        echo "Download complete!\n";
    } catch (\Exception $e) {
        echo "Download failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Generate speech
echo "\nGenerating speech...\n";
try {
    $audio = $tts
        ->model($modelId)
        ->speed(1.0)
        ->speak('Witaj świecie! To jest test biblioteki TTS w PHP.');
    
    $outputFile = 'output.wav';
    $audio->save($outputFile, 'wav');
    
    echo "Audio saved to: {$outputFile}\n";
    echo "Duration: " . round($audio->getDuration(), 2) . " seconds\n";
    echo "Sample rate: " . $audio->getSampleRate() . " Hz\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";
