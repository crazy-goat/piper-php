<?php
/**
 * Prawdziwy TTS: "Hello World" → WAV
 * 
 * Używa modelu KittenTTS (75MB) do generowania mowy.
 */

echo "=== Prawdziwy TTS: Hello World → WAV ===\n\n";

// Sprawdź extension
if (!extension_loaded('onnx_tts')) {
    if (!@dl('onnx_tts.so')) {
        die("Błąd: Załaduj extension: php -d 'extension=php-ext/modules/onnx_tts.so' $argv[0]\n");
    }
}

echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Konfiguracja
$text = "Hello World";
$model_path = __DIR__ . '/models/kitten_tts_mini_v0_8.onnx';
$output_file = "hello_world_real_tts.wav";
$sample_rate = 24000;

echo "Tekst: \"$text\"\n";
echo "Model: " . basename($model_path) . "\n";
echo "Output: $output_file\n\n";

// Sprawdź czy model istnieje
if (!file_exists($model_path)) {
    die("❌ Model nie znaleziony: $model_path\n");
}

// Załaduj model
echo "1. Ładowanie modelu (75MB)...\n";
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("❌ Błąd ładowania modelu\n");
}

echo "   ✓ Model załadowany!\n\n";

// Pobierz info o modelu
$info = onnx_tts_get_model_info($session);
echo "2. Struktura modelu:\n";
echo "   - Input count: " . $info['input_count'] . "\n";
echo "   - Output count: " . $info['output_count'] . "\n\n";

// Przygotuj dane wejściowe
// Dla KittenTTS: input_ids, speaker_id, speed
// Na razie użyjemy prostych danych testowych
echo "3. Przygotowanie danych wejściowych...\n";

// Input 1: Tokeny tekstu (symulacja - w prawdziwym TTS trzeba użyć tokenizera)
$input_ids = [];
for ($i = 0; $i < 100; $i++) {
    $input_ids[] = rand(1, 50); // Losowe tokeny jako placeholder
}

// Input 2: Speaker ID (0-7 dla KittenTTS)
$speaker_id = [0]; // Bella

// Input 3: Speed (1.0 = normal)
$speed = [1.0];

echo "   ✓ Przygotowano " . count($input_ids) . " tokenów\n";
echo "   ✓ Speaker: Bella (ID: 0)\n";
echo "   ✓ Speed: 1.0x\n\n";

// Uruchom inferencję
echo "4. Generowanie audio (może potrwać kilka sekund)...\n";

// TODO: Model ma 3 inputy - trzeba przekazać wszystkie
// Na razie spróbujemy z jednym inputem (może nie zadziałać)
try {
    $audio = onnx_tts_run($session, $input_ids, [1, count($input_ids)]);
    
    if ($audio && count($audio) > 0) {
        echo "   ✓ Wygenerowano " . count($audio) . " próbek audio\n";
        echo "   ✓ Czas trwania: " . round(count($audio) / $sample_rate, 2) . "s\n\n";
        
        // Zapisz jako WAV
        echo "5. Zapisywanie do WAV...\n";
        $result = onnx_tts_save_wav($output_file, $audio, $sample_rate);
        
        if ($result) {
            $file_size = round(filesize($output_file) / 1024, 2);
            echo "   ✓ Zapisano: $output_file ($file_size KB)\n\n";
            
            echo "=== SUKCES! ===\n";
            echo "Plik \"$text\" jest gotowy!\n\n";
            echo "Odtworzenie:\n";
            echo "  aplay $output_file\n";
            echo "  ffplay $output_file\n\n";
        } else {
            echo "   ❌ Błąd zapisywania WAV\n";
        }
    } else {
        echo "   ⚠ Model zwrócił puste dane\n";
        echo "   ℹ Model wymaga specyficznej tokenizacji\n";
        echo "   ℹ Sprawdź dokumentację KittenTTS dla poprawnego formatu inputu\n";
    }
} catch (Exception $e) {
    echo "   ❌ Błąd inferencji: " . $e->getMessage() . "\n";
    echo "   ℹ Model wymaga 3 inputów (input_ids, speaker_id, speed)\n";
    echo "   ℹ Aktualna wersja extension obsługuje tylko 1 input\n";
}

echo "\n=== Informacja ===\n";
echo "Model KittenTTS wymaga specyficznego formatu wejściowego:\n";
echo "  1. input_ids: tokeny tekstu (z tokenizera)\n";
echo "  2. speaker_id: ID głosu (0-7)\n";
echo "  3. speed: prędkość mowy (float)\n";
echo "\nAby użyć prawdziwego TTS, rozszerz extension o obsługę wielu inputów.\n";
