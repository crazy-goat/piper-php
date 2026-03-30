<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class ModelNotFoundException extends ModelException
{
    public function __construct(string $modelId, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "Model '{$modelId}' not found. Please download the model first.",
            $code,
            $previous
        );
    }
}
