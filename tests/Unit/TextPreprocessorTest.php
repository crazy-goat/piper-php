<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use OnnxTTS\TextPreprocessor;

class TextPreprocessorTest extends TestCase
{
    public function testNormalizeBasicText(): void
    {
        $preprocessor = new TextPreprocessor('pl');
        $result = $preprocessor->normalize('Witaj świecie');
        
        $this->assertEquals('Witaj świecie', $result);
    }
    
    public function testNormalizeNumbers(): void
    {
        $preprocessor = new TextPreprocessor('pl');
        $result = $preprocessor->normalize('Mam 123 złoty');
        
        // Numbers should be converted to words
        $this->assertStringNotContainsString('123', $result);
        $this->assertStringContainsString('sto', $result);
    }
    
    public function testNormalizeAbbreviations(): void
    {
        $preprocessor = new TextPreprocessor('pl');
        $preprocessor->addRule('/\bdr\b/', 'doktor');
        $result = $preprocessor->normalize('Dr Smith');
        
        $this->assertStringContainsString('doktor', strtolower($result));
    }
    
    public function testNormalizeWhitespace(): void
    {
        $preprocessor = new TextPreprocessor('pl');
        $result = $preprocessor->normalize("  Witaj   świecie  ");
        
        $this->assertEquals('Witaj świecie', $result);
    }
}
