<?php
/**
 * Prawdziwy TTS "Hello World" z modelem KittenTTS
 * 
 * Używa onnx_tts_run_multi() do obsługi wielu inputów:
 * - input_ids (int64): tokeny tekstu
 * - speaker_id (int64): ID głosu (0-7)
 * - speed (float): prędkość mowy
 */

echo "=== Prawdziwy TTS: Hello World ===\n\n";

// Sprawdź extension
if (!extension_loaded('onnx_tts')) {
    if (!@dl('onnx_tts.so')) {
        die("Błąd: Załaduj extension\n");
    }
}

echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Konfiguracja
$text = "Hello World";
$model_path = __DIR__ . '/models/kitten_tts_mini_v0_8.onnx';
$output_file = "hello_world_real.wav";
$sample_rate = 24000;
$voice_id = 0; // 0=Bella, 1=Jasper, 2=Luna, 3=Bruno, 4=Rosie, 5=Hugo, 6=Kiki, 7=Leo
$speed = 1.0;

echo "Tekst: \"$text\"\n";
echo "Głos: Bella (ID: $voice_id)\n";
echo "Prędkość: {$speed}x\n\n";

// Załaduj model
echo "1. Ładowanie modelu KittenTTS (75MB)...\n";
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("❌ Błąd ładowania modelu\n");
}

echo "   ✓ Model załadowany!\n\n";

// Tokenizacja tekstu (prosta - zamiana liter na kody ASCII)
echo "2. Tokenizacja tekstu...\n";
$tokens = [];
$text_lower = strtolower($text);
for ($i = 0; $i < strlen($text_lower); $i++) {
    $char = $text_lower[$i];
    if ($char >= 'a' && $char <= 'z') {
        $tokens[] = ord($char) - ord('a') + 1; // a=1, b=2, ...
    } elseif ($char == ' ') {
        $tokens[] = 0; // spacja = 0
    }
}

// Padding do długości 100 (wymagane przez model)
while (count($tokens) < 100) {
    $tokens[] = 0;
}
$tokens = array_slice($tokens, 0, 100);

echo "   ✓ Tokeny: " . implode(", ", array_slice($tokens, 0, 20)) . "...\n";
echo "   ✓ Długość: " . count($tokens) . "\n\n";

// Przygotuj inputy dla modelu
echo "3. Przygotowanie inputów...\n";

// Konwertuj tokeny na int64
$tokens_int = [];
foreach ($tokens as $token) {
    $tokens_int[] = (int)$token;
}

// Kolejność: tokens (int64), speaker (int64), speed (float)
$inputs = [
    // Input 1: tokeny tekstu (int64) - shape [1, 100]
    [
        'data' => $tokens_int,
        'shape' => [1, count($tokens_int)],
        'type' => 'int64'
    ],
    // Input 2: speaker ID (int64) - shape [1]
    [
        'data' => [$voice_id],
        'shape' => [1],
        'type' => 'int64'
    ],
    // Input 3: speed (float) - shape [1]
    [
        'data' => [$speed],
        'shape' => [1],
        'type' => 'float'
    ]
];

echo "   ✓ Input 1: " . count($tokens_int) . " tokenów (int64) shape [1, " . count($tokens_int) . "]\n";
echo "   ✓ Input 2: Speaker ID $voice_id (int64) shape [1]\n";
echo "   ✓ Input 3: Speed $speed (float) shape [1]\n\n";

// Uruchom inferencję
echo "4. Generowanie audio (to może potrwać 10-30 sekund)...\n";
$start_time = microtime(true);

$audio = onnx_tts_run_multi($session, $inputs);

$end_time = microtime(true);
$duration = round($end_time - $start_time, 2);

if (!$audio || count($audio) == 0) {
    die("❌ Błąd generowania audio\n");
}

echo "   ✓ Wygenerowano " . count($audio) . " próbek\n";
echo "   ✓ Czas generowania: {$duration}s\n";
echo "   ✓ Długość audio: " . round(count($audio) / $sample_rate, 2) . "s\n\n";

// Zapisz jako WAV
echo "5. Zapisywanie do WAV...\n";
$result = onnx_tts_save_wav($output_file, $audio, $sample_rate);

if ($result) {
    $file_size = round(filesize($output_file) / 1024, 2);
    echo "   ✓ Zapisano: $output_file ($file_size KB)\n\n";
    
    echo "=== SUKCES! ===\n";
    echo "\"$text\" zostało zamienione na mowę!\n\n";
    echo "Odtworzenie:\n";
    echo "  aplay $output_file\n";
    echo "  ffplay $output_file\n";
    echo "  vlc $output_file\n\n";
    
    echo "Pliki:\n";
    echo "  - Model: $model_path\n";
    echo "  - Audio: $output_file\n";
} else {
    echo "   ❌ Błąd zapisywania\n";
}
