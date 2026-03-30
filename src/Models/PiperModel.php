<?php

declare(strict_types=1);

namespace OnnxTTS\Models;

use OnnxTTS\AudioBuffer;
use OnnxTTS\TTSModel;
use OnnxTTS\Exception\InferenceException;

class PiperModel extends TTSModel
{
    private ?string $speakerId = null;
    private float $speed = 1.0;
    
    public function setSpeaker(string $speakerId): void
    {
        $this->speakerId = $speakerId;
    }
    
    public function setSpeed(float $speed): void
    {
        $this->speed = max(0.5, min(2.0, $speed));
    }
    
    public function synthesize(string $text): AudioBuffer
    {
        if ($this->session === null) {
            throw new \RuntimeException('Model not loaded. Call load() first.');
        }
        
        // Preprocess text
        $text = $this->preprocessText($text);
        
        // Tokenize text to input_ids
        $inputIds = $this->tokenize($text);
        
        // Prepare inputs
        $inputs = [
            'input_ids' => $inputIds,
        ];
        
        // Add speaker if specified and model supports it
        if ($this->speakerId !== null && !empty($this->getSpeakers())) {
            $speakerIndex = array_search($this->speakerId, $this->getSpeakers());
            if ($speakerIndex !== false) {
                $inputs['speaker_id'] = [$speakerIndex];
            }
        }
        
        // Run inference
        $outputs = $this->session->run($inputs);
        
        // Extract audio data
        if (!isset($outputs['output'])) {
            throw new InferenceException('Model output not found');
        }
        
        $audioData = $outputs['output']->getData();
        
        // Apply speed adjustment if needed
        if ($this->speed !== 1.0) {
            $audioData = $this->adjustSpeed($audioData, $this->speed);
        }
        
        return AudioBuffer::fromFloatArray($audioData, $this->getSampleRate());
    }
    
    private function tokenize(string $text): array
    {
        // Piper uses character-level tokenization
        // This is a simplified implementation
        $tokens = [];
        
        // Add start token
        $tokens[] = 0;
        
        // Convert characters to token IDs
        foreach (mb_str_split($text) as $char) {
            $tokenId = $this->charToToken($char);
            if ($tokenId !== null) {
                $tokens[] = $tokenId;
            }
        }
        
        // Add end token
        $tokens[] = 1;
        
        return $tokens;
    }
    
    private function charToToken(string $char): ?int
    {
        // Simplified tokenization
        $char = mb_strtolower($char);
        
        $map = [
            ' ' => 2,
            'a' => 3, 'ą' => 3,
            'b' => 4,
            'c' => 5, 'ć' => 5,
            'd' => 6,
            'e' => 7, 'ę' => 7,
            'f' => 8,
            'g' => 9,
            'h' => 10,
            'i' => 11,
            'j' => 12,
            'k' => 13,
            'l' => 14, 'ł' => 14,
            'm' => 15,
            'n' => 16, 'ń' => 16,
            'o' => 17, 'ó' => 17,
            'p' => 18,
            'q' => 19,
            'r' => 20,
            's' => 21, 'ś' => 21,
            't' => 22,
            'u' => 23,
            'v' => 24,
            'w' => 25,
            'x' => 26,
            'y' => 27,
            'z' => 28, 'ź' => 28, 'ż' => 28,
        ];
        
        return $map[$char] ?? 29;
    }
    
    private function adjustSpeed(array $audioData, float $speed): array
    {
        if ($speed === 1.0) {
            return $audioData;
        }
        
        // Simple linear interpolation for speed adjustment
        $newLength = (int) (count($audioData) / $speed);
        $result = [];
        
        for ($i = 0; $i < $newLength; $i++) {
            $srcIndex = $i * $speed;
            $index1 = (int) $srcIndex;
            $index2 = min($index1 + 1, count($audioData) - 1);
            $fraction = $srcIndex - $index1;
            
            $result[] = $audioData[$index1] * (1 - $fraction) + $audioData[$index2] * $fraction;
        }
        
        return $result;
    }
}
