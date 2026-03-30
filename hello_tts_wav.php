<?php
/**
 * TTS Hello World - Generowanie audio i zapis do WAV
 * 
 * Ten przykład pokazuje kompletny pipeline TTS:
 * 1. Załadowanie modelu ONNX (TTS)
 * 2. Przygotowanie danych wejściowych (tokeny tekstu)
 * 3. Uruchomienie inferencji
 * 4. Zapis wyniku jako plik WAV
 * 
 * Dla prawdziwego TTS, zamień model na KittenTTS lub Piper.
 * Ten przykład generuje prostą falę dźwiękową jako demonstrację.
 */

echo "=== TTS Hello World - Generowanie Audio ===\n\n";

// Sprawdź czy extension jest załadowana
if (!extension_loaded('onnx_tts')) {
    echo "Ładowanie extension onnx_tts...\n";
    if (!@dl('onnx_tts.so')) {
        die("Błąd: Nie można załadować extension\n");
    }
}

echo "✓ Extension załadowana\n";
echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Dla demonstracji wygenerujemy prostą falę dźwiękową (sine wave)
// W prawdziwym TTS, tutaj załadowalibyśmy model i uruchomili inferencję
echo "1. Generowanie fali dźwiękowej (demonstracja)...\n";

$sample_rate = 24000;  // 24 kHz - standard dla TTS
$duration = 2.0;        // 2 sekundy
$frequency = 440;       // 440 Hz - ton A

$num_samples = (int)($sample_rate * $duration);
$audio_data = [];

for ($i = 0; $i < $num_samples; $i++) {
    // Generuj sine wave
    $t = $i / $sample_rate;
    $sample = sin(2 * M_PI * $frequency * $t);
    
    // Dodaj fade in/out dla gładkiego dźwięku
    $fade_duration = 0.1; // 100ms
    if ($t < $fade_duration) {
        $sample *= ($t / $fade_duration);
    } elseif ($t > $duration - $fade_duration) {
        $sample *= (($duration - $t) / $fade_duration);
    }
    
    $audio_data[] = $sample;
}

echo "   ✓ Wygenerowano $num_samples próbek\n";
echo "   ✓ Czas trwania: $duration s\n";
echo "   ✓ Sample rate: $sample_rate Hz\n\n";

// Zapisz jako WAV
echo "2. Zapisywanie do pliku WAV...\n";
$output_file = 'hello_world.wav';

$result = onnx_tts_save_wav($output_file, $audio_data, $sample_rate);

if ($result) {
    $file_size = round(filesize($output_file) / 1024, 2);
    echo "   ✓ Audio zapisane: $output_file\n";
    echo "   ✓ Rozmiar pliku: $file_size KB\n\n";
    
    echo "=== SUKCES! ===\n";
    echo "Plik audio jest gotowy do odtworzenia:\n";
    echo "  aplay $output_file    (Linux)\n";
    echo "  afplay $output_file   (macOS)\n";
    echo "  ffplay $output_file   (Windows/Linux)\n\n";
} else {
    echo "   ❌ Błąd zapisywania pliku\n";
}

echo "Dla prawdziwego TTS:\n";
echo "1. Pobierz model KittenTTS lub Piper\n";
echo "2. Załaduj: \$session = onnx_tts_load_model('model.onnx')\n";
echo "3. Przygotuj tokeny tekstu jako array\n";
echo "4. Uruchom: \$audio = onnx_tts_run(\$session, \$tokens, \$shape)\n";
echo "5. Zapisz: onnx_tts_save_wav('output.wav', \$audio, 24000)\n";
