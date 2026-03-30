<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class UnsupportedModelException extends ModelException
{
    public function __construct(string $format, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "Model format '{$format}' is not supported.",
            $code,
            $previous
        );
    }
}
