<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class ModelException extends OnnxTTSException
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
