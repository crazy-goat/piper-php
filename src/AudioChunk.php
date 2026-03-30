<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS;

final readonly class AudioChunk
{
    public function __construct(
        /** Raw 16-bit signed little-endian PCM data */
        public string $pcmData,
        /** Sample rate in Hz (e.g. 22050) */
        public int $sampleRate,
        /** True if this is the last chunk */
        public bool $isLast,
    ) {
    }
}
