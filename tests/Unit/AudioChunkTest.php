<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Unit;

use CrazyGoat\PiperTTS\AudioChunk;
use PHPUnit\Framework\TestCase;

final class AudioChunkTest extends TestCase
{
    public function test_constructor_assigns_properties(): void
    {
        $pcmData = "\x00\x00\xFF\xFF";
        $chunk = new AudioChunk($pcmData, 22050, false);

        self::assertSame($pcmData, $chunk->pcmData);
        self::assertSame(22050, $chunk->sampleRate);
        self::assertFalse($chunk->isLast);
    }

    public function test_is_last_flag(): void
    {
        $chunk = new AudioChunk('', 16000, true);

        self::assertTrue($chunk->isLast);
    }

    public function test_empty_pcm_data(): void
    {
        $chunk = new AudioChunk('', 22050, true);

        self::assertSame('', $chunk->pcmData);
        self::assertSame(22050, $chunk->sampleRate);
    }
}
