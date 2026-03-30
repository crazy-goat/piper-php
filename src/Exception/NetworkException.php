<?php

declare(strict_types=1);

namespace OnnxTTS\Exception;

use Exception;

class NetworkException extends OnnxTTSException
{
    public function __construct(string $url, string $reason, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            "Network request to '{$url}' failed: {$reason}",
            $code,
            $previous
        );
    }
}
