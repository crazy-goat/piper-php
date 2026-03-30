<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class InferenceException extends OnnxTTSException
{
    public function __construct(string $reason, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct("Inference failed: {$reason}", $code, $previous);
    }
}
