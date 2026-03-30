<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class OnnxRuntimeException extends OnnxTTSException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("ONNX Runtime error: {$message}", $code, $previous);
    }
}
