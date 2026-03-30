<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class ModelCorruptedException extends ModelException
{
    public function __construct(string $modelId, string $reason, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "Model '{$modelId}' is corrupted: {$reason}",
            $code,
            $previous
        );
    }
}
