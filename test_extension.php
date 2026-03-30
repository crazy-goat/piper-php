<?php
/**
 * Test PHP Extension for ONNX TTS
 */

// Sprawdź czy extension jest załadowana
if (!function_exists('onnx_tts_version')) {
    die("Błąd: Extension onnx_tts nie jest załadowana!\n");
}

echo "=== ONNX TTS PHP Extension Test ===\n\n";

// Test 1: Wersja ONNX Runtime
echo "1. ONNX Runtime Version: " . onnx_tts_version() . "\n";

// Test 2: Sprawdź czy to string
$version = onnx_tts_version();
if (is_string($version) && strlen($version) > 0) {
    echo "2. ✓ Version returned correctly\n";
} else {
    echo "2. ✗ Version error\n";
}

echo "\n=== Test zakończony sukcesem! ===\n";
