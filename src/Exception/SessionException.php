<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class SessionException extends OnnxTTSException
{
    public function __construct(string $modelPath, string $reason, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "Failed to create ONNX session for model '{$modelPath}': {$reason}",
            $code,
            $previous
        );
    }
}
