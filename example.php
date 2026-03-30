<?php

/**
 * Example: Basic TTS usage with ONNX PHP TTS library
 * 
 * This example demonstrates how to:
 * 1. Initialize the ONNX Runtime
 * 2. Set up model management
 * 3. Download and load a TTS model
 * 4. Generate speech from text
 * 5. Save audio to file
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OnnxTTS\OnnxRuntime;
use OnnxTTS\ModelManager;
use OnnxTTS\TTS;

// Configuration
$ortLibraryPath = getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21';
$cacheDirectory = getenv('HOME') . '/.cache/onnx-tts';

echo "=== ONNX PHP TTS Example ===\n\n";

// Step 1: Initialize ONNX Runtime
echo "1. Initializing ONNX Runtime...\n";
try {
    $runtime = new OnnxRuntime($ortLibraryPath);
    echo "   ✓ ONNX Runtime loaded (version: " . $runtime->getVersion() . ")\n";
} catch (\Exception $e) {
    echo "   ✗ Failed to load ONNX Runtime: " . $e->getMessage() . "\n";
    echo "   Please install ONNX Runtime: ./scripts/install-ort.sh\n";
    exit(1);
}

// Step 2: Set up ModelManager
echo "\n2. Setting up ModelManager...\n";
$manager = new ModelManager($cacheDirectory);
echo "   ✓ Cache directory: {$cacheDirectory}\n";

// Show available models
echo "\n3. Checking available models...\n";
$availableModels = $manager->listAvailable();
if (empty($availableModels)) {
    echo "   ℹ No models downloaded yet\n";
} else {
    echo "   ✓ Downloaded models:\n";
    foreach ($availableModels as $model) {
        echo "     - {$model}\n";
    }
}

// Show remote models
echo "\n4. Available models for download:\n";
$remoteModels = $manager->listRemote();
foreach ($remoteModels as $id => $info) {
    echo "   - {$id} (from {$info['repo']})\n";
}

// Step 5: Download a model if not present
$modelId = 'piper-pl';
echo "\n5. Checking model: {$modelId}\n";

if (!$manager->isDownloaded($modelId)) {
    echo "   ℹ Model not found locally. Downloading...\n";
    try {
        $manager->download($modelId);
        echo "   ✓ Download complete!\n";
    } catch (\Exception $e) {
        echo "   ✗ Download failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "   ✓ Model already downloaded\n";
}

// Step 6: Create TTS instance
echo "\n6. Creating TTS instance...\n";
$tts = new TTS($runtime, $manager);
echo "   ✓ TTS ready\n";

// Step 7: Generate speech
echo "\n7. Generating speech...\n";
$text = 'Witaj świecie! To jest przykład użycia biblioteki TTS w PHP.';
echo "   Text: \"{$text}\"\n";

try {
    $audio = $tts
        ->model($modelId)
        ->speed(1.0)
        ->speak($text);
    
    echo "   ✓ Speech generated successfully!\n";
    echo "   - Duration: " . round($audio->getDuration(), 2) . " seconds\n";
    echo "   - Sample rate: " . $audio->getSampleRate() . " Hz\n";
    
    // Step 8: Save to file
    $outputFile = 'output.wav';
    echo "\n8. Saving audio to: {$outputFile}\n";
    $audio->save($outputFile, 'wav');
    echo "   ✓ Audio saved successfully!\n";
    
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Example completed successfully! ===\n";
echo "Output file: {$outputFile}\n";
