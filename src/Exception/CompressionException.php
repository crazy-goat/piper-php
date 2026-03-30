<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

class CompressionException extends AudioException
{
    public function __construct(string $format, string $reason, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Audio compression to '{$format}' failed: {$reason}. Consider using WAV format as a fallback.",
            $code,
            $previous
        );
    }
}
