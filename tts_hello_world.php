<?php
/**
 * TTS: "Hello World" → WAV
 * 
 * Ten skrypt zamienia tekst "Hello World" na plik audio WAV.
 * 
 * WERSJA DEMONSTRACYJNA (bez modelu TTS):
 * Generuje dźwięk, który symuluje mowę używając modulacji fali.
 * 
 * Dla prawdziwego TTS, potrzebujesz modelu ONNX (KittenTTS/Piper).
 */

echo "=== TTS: Hello World → WAV ===\n\n";

// Sprawdź czy extension jest załadowana
if (!extension_loaded('onnx_tts')) {
    if (!@dl('onnx_tts.so')) {
        die("Błąd: Załaduj extension: php -d 'extension=php-ext/modules/onnx_tts.so' $argv[0]\n");
    }
}

echo "✓ ONNX Runtime: " . onnx_tts_version() . "\n\n";

// Konfiguracja
$text = "Hello World";
$output_file = "hello_world_tts.wav";
$sample_rate = 24000;

echo "Tekst: \"$text\"\n";
echo "Output: $output_file\n\n";

// Sprawdź czy mamy model TTS
$model_path = __DIR__ . '/models/kitten-tts.onnx';
$has_real_model = file_exists($model_path) && filesize($model_path) > 1000000;

if ($has_real_model) {
    echo "✓ Znaleziono model TTS\n";
    echo "  Uruchamianie prawdziwego TTS...\n\n";
    
    // TODO: Tutaj załaduj model i uruchom TTS
    // $session = onnx_tts_load_model($model_path);
    // $tokens = tokenize_text($text); // Funkcja do zaimplementowania
    // $audio = onnx_tts_run($session, $tokens, [1, count($tokens)]);
    
    echo "  (Model TTS wymaga tokenizacji - zobacz dokumentację modelu)\n\n";
} else {
    echo "ℹ Brak modelu TTS - używam wersji demonstracyjnej\n";
    echo "  Generuję dźwięk symboliczny dla \"$text\"...\n\n";
    
    // Generuj dźwięk demonstracyjny
    // "Hello" - niższy ton, "World" - wyższy ton
    $audio_data = [];
    
    // "Hello" - 1 sekunda, ton 300Hz
    $duration_hello = 1.0;
    $freq_hello = 300;
    $samples_hello = (int)($sample_rate * $duration_hello);
    
    for ($i = 0; $i < $samples_hello; $i++) {
        $t = $i / $sample_rate;
        // Sine wave z vibrato
        $vibrato = sin(2 * M_PI * 5 * $t) * 10; // 5Hz vibrato
        $sample = sin(2 * M_PI * ($freq_hello + $vibrato) * $t);
        
        // Fade in/out
        if ($t < 0.05) $sample *= ($t / 0.05);
        if ($t > $duration_hello - 0.05) $sample *= (($duration_hello - $t) / 0.05);
        
        $audio_data[] = $sample * 0.5;
    }
    
    // Pauza 0.2s
    $pause_samples = (int)($sample_rate * 0.2);
    for ($i = 0; $i < $pause_samples; $i++) {
        $audio_data[] = 0.0;
    }
    
    // "World" - 1 sekunda, ton 450Hz (wyższy)
    $duration_world = 1.0;
    $freq_world = 450;
    $samples_world = (int)($sample_rate * $duration_world);
    
    for ($i = 0; $i < $samples_world; $i++) {
        $t = $i / $sample_rate;
        // Sine wave z lekkim tremolo
        $tremolo = 1.0 + sin(2 * M_PI * 8 * $t) * 0.1; // 8Hz tremolo
        $sample = sin(2 * M_PI * $freq_world * $t) * $tremolo;
        
        // Fade in/out
        if ($t < 0.05) $sample *= ($t / 0.05);
        if ($t > $duration_world - 0.05) $sample *= (($duration_world - $t) / 0.05);
        
        $audio_data[] = $sample * 0.5;
    }
    
    echo "  ✓ Wygenerowano " . count($audio_data) . " próbek\n";
    echo "  ✓ Czas trwania: " . round(count($audio_data) / $sample_rate, 2) . "s\n";
    echo "  ✓ Sample rate: $sample_rate Hz\n\n";
}

// Zapisz jako WAV
echo "Zapisywanie do WAV...\n";
$result = onnx_tts_save_wav($output_file, $audio_data, $sample_rate);

if ($result) {
    $file_size = round(filesize($output_file) / 1024, 2);
    echo "  ✓ Zapisano: $output_file ($file_size KB)\n\n";
    
    echo "=== SUKCES! ===\n";
    echo "Plik \"$text\" jest gotowy!\n\n";
    echo "Odtworzenie:\n";
    echo "  aplay $output_file\n";
    echo "  ffplay $output_file\n";
    echo "  vlc $output_file\n\n";
    
    if (!$has_real_model) {
        echo "💡 To jest wersja demonstracyjna (sine wave).\n";
        echo "   Dla prawdziwego TTS:\n";
        echo "   1. Pobierz model: https://huggingface.co/KittenML/kitten-tts-nano-0.8\n";
        echo "   2. Zapisz jako: models/kitten-tts.onnx\n";
        echo "   3. Uruchom ponownie ten skrypt\n";
    }
} else {
    echo "  ❌ Błąd zapisywania\n";
}
