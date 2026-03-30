<?php

declare(strict_types=1);

namespace OnnxTTS;

use OnnxTTS\Exception\InferenceException;

class OrtSession
{
    private \FFI $ffi;
    private $api;
    private $session;
    private ?array $inputNames = null;
    private ?array $outputNames = null;

    public function __construct(\FFI $ffi, $api, $session)
    {
        $this->ffi = $ffi;
        $this->api = $api;
        $this->session = $session;
    }

    public function getInputNames(): array
    {
        if ($this->inputNames === null) {
            $this->inputNames = $this->fetchNames('input');
        }
        return $this->inputNames;
    }

    public function getOutputNames(): array
    {
        if ($this->outputNames === null) {
            $this->outputNames = $this->fetchNames('output');
        }
        return $this->outputNames;
    }

    private function fetchNames(string $type): array
    {
        $countPtr = $this->ffi->new('size_t[1]');
        $method = $type === 'input' ? 'SessionGetInputCount' : 'SessionGetOutputCount';

        $status = ($this->api->$method)($this->session, $countPtr);
        if ($status !== null) {
            $this->handleError($status);
        }

        $count = (int) $countPtr[0];
        $names = [];

        for ($i = 0; $i < $count; $i++) {
            // For now, return generic names since we need allocator for GetInputName
            $names[] = $type . '_' . $i;
        }

        return $names;
    }

    public function run(array $inputs): array
    {
        // Simplified implementation - just return empty array for now
        // Full implementation would create tensors, run inference, extract outputs
        return [];
    }

    public function __destruct()
    {
        if ($this->session !== null) {
            ($this->api->ReleaseSession)($this->session);
        }
    }

    private function handleError($status): void
    {
        $message = ($this->api->GetErrorMessage)($status);
        $errorMsg = \FFI::string($message);
        ($this->api->ReleaseStatus)($status);
        throw new InferenceException($errorMsg);
    }
}
