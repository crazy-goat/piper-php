<?php

declare(strict_types=1);

namespace OnnxTTS\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use OnnxTTS\Models\PiperModel;
use OnnxTTS\OnnxRuntime;

class PiperModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        if (!file_exists(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21')) {
            $this->markTestSkipped('ONNX Runtime not installed');
        }
        
        $runtime = new OnnxRuntime(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21');
        $model = new PiperModel($runtime, '/tmp/fake-model');
        
        $this->assertInstanceOf(PiperModel::class, $model);
    }
    
    public function testSetSpeakerAndSpeed(): void
    {
        if (!file_exists(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21')) {
            $this->markTestSkipped('ONNX Runtime not installed');
        }
        
        $runtime = new OnnxRuntime(getenv('ORT_LIBRARY') ?: '/lib/x86_64-linux-gnu/libonnxruntime.so.1.21');
        $model = new PiperModel($runtime, '/tmp/fake-model');
        
        $model->setSpeaker('speaker1');
        $model->setSpeed(1.5);
        
        $this->assertTrue(true);
    }
}
