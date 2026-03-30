<?php

declare(strict_types=1);

namespace OnnxTTS;

class TextPreprocessor
{
    private string $language;
    private array $rules = [];
    
    public function __construct(string $language = 'pl')
    {
        $supportedLanguages = ['pl', 'en'];
        if (!in_array($language, $supportedLanguages, true)) {
            throw new \InvalidArgumentException("Unsupported language: {$language}. Supported: " . implode(', ', $supportedLanguages));
        }
        $this->language = $language;
        $this->loadDefaultRules();
    }
    
    public function normalize(string $text): string
    {
        $text = trim($text);
        
        $result = preg_replace('/\s+/', ' ', $text);
        if ($result === null) {
            throw new \RuntimeException('Regex error in whitespace normalization');
        }
        $text = $result;
        
        foreach ($this->rules as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $text);
            if ($result === null) {
                throw new \RuntimeException('Regex error in rules normalization');
            }
            $text = $result;
        }
        
        $text = $this->normalizeNumbers($text);
        
        return $text;
    }
    
    public function addRule(string $pattern, string $replacement): void
    {
        $this->rules[$pattern] = $replacement;
    }
    
    private function loadDefaultRules(): void
    {
        switch ($this->language) {
            case 'pl':
                $this->rules['/\bdr\b/i'] = 'doktor';
                $this->rules['/\bprof\b/i'] = 'profesor';
                $this->rules['/\bnp\b/i'] = 'na przykład';
                $this->rules['/\bitd\b/i'] = 'i tak dalej';
                break;
            case 'en':
                $this->rules['/\bdr\b/i'] = 'doctor';
                $this->rules['/\bprof\b/i'] = 'professor';
                $this->rules['/\be\.g\b/i'] = 'for example';
                $this->rules['/\betc\b/i'] = 'et cetera';
                break;
        }
    }
    
    private function normalizeNumbers(string $text): string
    {
        $result = preg_replace_callback('/\d+/', function ($matches) {
            return $this->numberToWords((int) $matches[0]);
        }, $text);
        if ($result === null) {
            throw new \RuntimeException('Regex error in number normalization');
        }
        return $result;
    }
    
    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }
        
        if ($number < 100) {
            return $this->smallNumberToWords($number);
        }

        if ($number < 1000) {
            $hundreds = [
                'pl' => ['', 'sto', 'dwieście', 'trzysta', 'czterysta', 'pięćset',
                         'sześćset', 'siedemset', 'osiemset', 'dziewięćset'],
                'en' => ['', 'one hundred', 'two hundred', 'three hundred', 'four hundred', 'five hundred',
                         'six hundred', 'seven hundred', 'eight hundred', 'nine hundred'],
            ];
            $h = (int) ($number / 100);
            $remainder = $number % 100;
            $result = $hundreds[$this->language][$h];
            if ($remainder > 0) {
                $result .= ' ' . $this->smallNumberToWords($remainder);
            }
            return $result;
        }
        
        return (string) $number;
    }
    
    private function smallNumberToWords(int $number): string
    {
        $units = [
            'pl' => ['', 'jeden', 'dwa', 'trzy', 'cztery', 'pięć', 'sześć', 'siedem', 'osiem', 'dziewięć'],
            'en' => ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine']
        ];
        
        $teens = [
            'pl' => ['dziesięć', 'jedenaście', 'dwanaście', 'trzynaście', 'czternaście', 
                     'piętnaście', 'szesnaście', 'siedemnaście', 'osiemnaście', 'dziewiętnaście'],
            'en' => ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 
                     'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen']
        ];
        
        $tens = [
            'pl' => ['', '', 'dwadzieścia', 'trzydzieści', 'czterdzieści', 'pięćdziesiąt', 
                     'sześćdziesiąt', 'siedemdziesiąt', 'osiemdziesiąt', 'dziewięćdziesiąt'],
            'en' => ['', '', 'twenty', 'thirty', 'forty', 'fifty', 
                     'sixty', 'seventy', 'eighty', 'ninety']
        ];
        
        $lang = $this->language;
        
        if ($number < 10) {
            return $units[$lang][$number];
        }
        
        if ($number < 20) {
            return $teens[$lang][$number - 10];
        }
        
        $ten = (int) ($number / 10);
        $unit = $number % 10;
        
        if ($unit === 0) {
            return $tens[$lang][$ten];
        }
        
        return $tens[$lang][$ten] . ' ' . $units[$lang][$unit];
    }
}
