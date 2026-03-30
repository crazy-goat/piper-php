<?php
/**
 * Hello World - Kitten TTS Example
 * 
 * Wymagania:
 * 1. Zainstaluj extension: cd php-ext && make && sudo make install
 * 2. Pobierz model KittenTTS nano (25MB):
 *    - Wejdź na: https://huggingface.co/KittenML/kitten-tts-nano-0.8-int8
 *    - Kliknij "Files and versions"
 *    - Pobierz plik "model.onnx"
 *    - Zapisz jako: models/kitten-nano.onnx
 * 3. Uruchom: php hello_kitten.php
 */

echo "=== Kitten TTS - Hello World ===\n\n";

// Sprawdź czy extension jest załadowana
if (!extension_loaded('onnx_tts')) {
    echo "Ładowanie extension onnx_tts...\n";
    if (!@dl('onnx_tts.so')) {
        echo "Błąd: Nie można załadować extension\n";
        echo "Zainstaluj extension:\n";
        echo "  cd php-ext && make && sudo make install\n";
        exit(1);
    }
}

echo "✓ Extension załadowana\n";
echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Ścieżka do modelu
$model_path = __DIR__ . '/models/kitten-nano.onnx';

// Sprawdź czy model istnieje
if (!file_exists($model_path)) {
    echo "❌ Model nie znaleziony: $model_path\n\n";
    echo "Instrukcja pobierania modelu:\n";
    echo "1. Wejdź na: https://huggingface.co/KittenML/kitten-tts-nano-0.8-int8\n";
    echo "2. Kliknij 'Files and versions'\n";
    echo "3. Pobierz plik 'model.onnx' (ok. 25MB)\n";
    echo "4. Zapisz go jako: models/kitten-nano.onnx\n\n";
    exit(1);
}

echo "✓ Znaleziono model: " . basename($model_path) . "\n";
echo "  Rozmiar: " . round(filesize($model_path) / 1024 / 1024, 2) . " MB\n\n";

// Załaduj model
echo "Ładowanie modelu...\n";
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("❌ Błąd ładowania modelu\n");
}

echo "✓ Model załadowany pomyślnie!\n\n";

// Pobierz informacje o modelu
echo "Informacje o modelu:\n";
$info = onnx_tts_get_model_info($session);

if ($info) {
    echo "  - Input count: " . $info['input_count'] . "\n";
    echo "  - Output count: " . $info['output_count'] . "\n";
} else {
    echo "  ⚠ Nie udało się pobrać informacji\n";
}

echo "\n=== Gotowe! ===\n";
echo "\nNastępne kroki:\n";
echo "- Dodaj funkcję onnx_tts_run() do extension\n";
echo "- Przygotuj dane wejściowe (tekst → tokeny)\n";
echo "- Uruchom inferencję: onnx_tts_run(\$session, \$input_data)\n";
echo "- Zapisz wynik jako plik WAV\n";
