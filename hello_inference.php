<?php
/**
 * Hello World - ONNX Inference Example
 * 
 * Ten przykład pokazuje jak uruchomić inferencję na modelu ONNX.
 * Używamy modelu MNIST (klasyfikacja cyfr) jako przykładu.
 * 
 * Dla TTS, zamień model MNIST na model KittenTTS lub Piper.
 */

echo "=== ONNX PHP Extension - Hello World (Inference) ===\n\n";

// Sprawdź czy extension jest załadowana
if (!extension_loaded('onnx_tts')) {
    echo "Ładowanie extension onnx_tts...\n";
    if (!@dl('onnx_tts.so')) {
        echo "Błąd: Nie można załadować extension\n";
        echo "Użyj: php -d 'extension=php-ext/modules/onnx_tts.so' hello_inference.php\n";
        exit(1);
    }
}

echo "✓ Extension załadowana\n";
echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Ścieżka do modelu MNIST
$model_path = __DIR__ . '/models/tiny_model.onnx';

if (!file_exists($model_path)) {
    echo "❌ Model nie znaleziony: $model_path\n";
    echo "Pobieranie modelu testowego MNIST...\n";
    
    $model_dir = __DIR__ . '/models';
    if (!is_dir($model_dir)) {
        mkdir($model_dir, 0755, true);
    }
    
    $url = 'https://github.com/onnx/models/raw/main/validated/vision/classification/mnist/model/mnist-12.onnx';
    file_put_contents($model_path, file_get_contents($url));
    echo "✓ Model pobrany!\n\n";
}

// Załaduj model
echo "1. Ładowanie modelu...\n";
$session = onnx_tts_load_model($model_path);

if (!$session) {
    die("❌ Błąd ładowania modelu\n");
}

echo "   ✓ Model załadowany!\n\n";

// Pobierz informacje o modelu
echo "2. Informacje o modelu:\n";
$info = onnx_tts_get_model_info($session);
echo "   - Input count: " . $info['input_count'] . "\n";
echo "   - Output count: " . $info['output_count'] . "\n\n";

// Przygotuj dane wejściowe (obraz 28x28 = 784 piksele)
echo "3. Przygotowanie danych wejściowych...\n";
$input_data = [];

// Stwórz "pusty" obraz (same zera) z jednym białym pikselem w środku
// MNIST wymaga kształtu [1, 1, 28, 28] - batch, channels, height, width
for ($i = 0; $i < 784; $i++) {
    $input_data[] = 0.0;
}

// Dodaj kilka pikseli w środku (symulacja cyfry "1")
$center = 14 * 28 + 14; // środek obrazu 28x28
$input_data[$center] = 1.0;
$input_data[$center - 28] = 1.0;  // góra
$input_data[$center + 28] = 1.0;  // dół

echo "   ✓ Przygotowano " . count($input_data) . " wartości (1x1x28x28)\n\n";

// Uruchom inferencję
echo "4. Uruchamianie inferencji...\n";
$shape = [1, 1, 28, 28]; // batch, channels, height, width dla MNIST
$result = onnx_tts_run($session, $input_data, $shape);

if (!$result) {
    die("❌ Błąd inferencji\n");
}

echo "   ✓ Inferencja zakończona!\n";
echo "   - Wynik: " . count($result) . " wartości\n\n";

// Wyświetl wyniki (prawdopodobieństwa dla cyfr 0-9)
echo "5. Wyniki (prawdopodobieństwa dla cyfr 0-9):\n";
$max_prob = 0;
$predicted_digit = 0;

foreach ($result as $digit => $probability) {
    $percent = round($probability * 100, 2);
    echo "   Cyfra $digit: $percent%\n";
    
    if ($probability > $max_prob) {
        $max_prob = $probability;
        $predicted_digit = $digit;
    }
}

echo "\n=== Wynik: Przewidziana cyfra to $predicted_digit ===\n";
echo "   (Prawdopodobieństwo: " . round($max_prob * 100, 2) . "%)\n";

echo "\n✅ SUKCES! Inferencja ONNX działa!\n";
echo "\nTeraz możesz:\n";
echo "1. Pobrać model TTS (KittenTTS lub Piper)\n";
echo "2. Przygotować tekst jako wektor liczb (tokeny)\n";
echo "3. Uruchomić onnx_tts_run() na modelu TTS\n";
echo "4. Zapisać wynik jako plik audio WAV\n";
