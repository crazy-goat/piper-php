<?php
/**
 * Test różnych kombinacji typów dla KittenTTS
 */

echo "=== Testowanie typów inputów KittenTTS ===\n\n";

if (!extension_loaded('onnx_tts')) {
    if (!@dl('onnx_tts.so')) {
        die("Błąd: Załaduj extension\n");
    }
}

$model_path = __DIR__ . '/models/kitten_tts_mini_v0_8.onnx';
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("Błąd ładowania modelu\n");
}

echo "✓ Model załadowany\n";
echo "Info: ";
var_dump(onnx_tts_get_model_info($session));
echo "\n";

// Przygotuj dane testowe
$tokens = [];
for ($i = 0; $i < 100; $i++) $tokens[] = $i;

// Test 1: Wszystkie float
echo "Test 1: tokens(float), speaker(float), speed(float)\n";
$inputs1 = [
    ['data' => $tokens, 'shape' => [1, 100], 'type' => 'float'],
    ['data' => [0], 'shape' => [1], 'type' => 'float'],
    ['data' => [1.0], 'shape' => [1], 'type' => 'float']
];
$result1 = @onnx_tts_run_multi($session, $inputs1);
echo $result1 ? "✓ Działa! Wygenerowano " . count($result1) . " próbek\n" : "✗ Błąd\n";
echo "\n";

// Test 2: tokens int64, reszta float
echo "Test 2: tokens(int64), speaker(float), speed(float)\n";
$tokens_int = [];
foreach ($tokens as $t) $tokens_int[] = (int)$t;
$inputs2 = [
    ['data' => $tokens_int, 'shape' => [1, 100], 'type' => 'int64'],
    ['data' => [0], 'shape' => [1], 'type' => 'float'],
    ['data' => [1.0], 'shape' => [1], 'type' => 'float']
];
$result2 = @onnx_tts_run_multi($session, $inputs2);
echo $result2 ? "✓ Działa! Wygenerowano " . count($result2) . " próbek\n" : "✗ Błąd\n";
echo "\n";

// Test 3: tokens int64, speaker int64, speed float
echo "Test 3: tokens(int64), speaker(int64), speed(float)\n";
$inputs3 = [
    ['data' => $tokens_int, 'shape' => [1, 100], 'type' => 'int64'],
    ['data' => [0], 'shape' => [1], 'type' => 'int64'],
    ['data' => [1.0], 'shape' => [1], 'type' => 'float']
];
$result3 = @onnx_tts_run_multi($session, $inputs3);
echo $result3 ? "✓ Działa! Wygenerowano " . count($result3) . " próbek\n" : "✗ Błąd\n";
echo "\n";

// Test 4: Inna kolejność - speaker, tokens, speed
echo "Test 4: speaker(float), tokens(float), speed(float)\n";
$inputs4 = [
    ['data' => [0], 'shape' => [1], 'type' => 'float'],
    ['data' => $tokens, 'shape' => [1, 100], 'type' => 'float'],
    ['data' => [1.0], 'shape' => [1], 'type' => 'float']
];
$result4 = @onnx_tts_run_multi($session, $inputs4);
echo $result4 ? "✓ Działa! Wygenerowano " . count($result4) . " próbek\n" : "✗ Błąd\n";
echo "\n";

echo "=== Koniec testów ===\n";
