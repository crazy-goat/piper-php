<?php
/**
 * Hello World dla ONNX PHP Extension
 * 
 * Ten przykład pokazuje jak załadować model ONNX i sprawdzić jego strukturę.
 * Używamy modelu MNIST (klasyfikacja cyfr) jako przykładu.
 * 
 * Dla TTS, zamień model MNIST na model Piper lub Kitten TTS.
 */

echo "=== ONNX PHP Extension - Hello World ===\n\n";

// 1. Sprawdź czy extension jest załadowana
if (!extension_loaded('onnx_tts')) {
    echo "Ładowanie extension...\n";
    if (!dl('onnx_tts.so')) {
        die("Błąd: Nie można załadować extension onnx_tts\n");
    }
}

// 2. Sprawdź wersję ONNX Runtime
echo "1. ONNX Runtime Version: " . onnx_tts_version() . "\n\n";

// 3. Ścieżka do modelu
$model_path = __DIR__ . '/models/tiny_model.onnx';

if (!file_exists($model_path)) {
    echo "Model nie znaleziony: $model_path\n";
    echo "Pobieranie modelu testowego...\n";
    
    // Pobierz mały model MNIST do testów
    $url = 'https://github.com/onnx/models/raw/main/validated/vision/classification/mnist/model/mnist-12.onnx';
    $model_dir = __DIR__ . '/models';
    
    if (!is_dir($model_dir)) {
        mkdir($model_dir, 0755, true);
    }
    
    file_put_contents($model_path, file_get_contents($url));
    echo "Model pobrany!\n\n";
}

// 4. Załaduj model
echo "2. Ładowanie modelu: $model_path\n";
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("Błąd: Nie udało się załadować modelu\n");
}

echo "   ✓ Model załadowany pomyślnie!\n\n";

// 5. Pobierz informacje o modelu
echo "3. Informacje o modelu:\n";
$info = onnx_tts_get_model_info($session);

if ($info) {
    echo "   - Liczba inputów: " . $info['input_count'] . "\n";
    echo "   - Liczba outputów: " . $info['output_count'] . "\n";
} else {
    echo "   ✗ Nie udało się pobrać informacji\n";
}

echo "\n=== Sukces! Model działa ===\n";
echo "\nTeraz możesz:\n";
echo "1. Pobrać model TTS (np. Piper: https://huggingface.co/rhasspy/piper-voices)\n";
echo "2. Zamienić ścieżkę w kodzie na model TTS\n";
echo "3. Przygotować dane wejściowe (tekst)\n";
echo "4. Uruchomić inferencję\n";
