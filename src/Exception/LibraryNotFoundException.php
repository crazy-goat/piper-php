<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class LibraryNotFoundException extends OnnxTTSException
{
    public function __construct(string $libraryPath, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "ONNX Runtime library not found at '{$libraryPath}'. Please install the ONNX Runtime library and ensure it is accessible.",
            $code,
            $previous
        );
    }
}
