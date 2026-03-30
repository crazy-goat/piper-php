<?php

declare(strict_types=1);

namespace OnnxTTS;

use FFI;
use OnnxTTS\Exception\OnnxRuntimeException;
use OnnxTTS\FFI\OnnxRuntimeFFI;

class OnnxRuntime
{
    private FFI $ffi;
    private $api;
    private $env;

    public function __construct(string $libraryPath)
    {
        $this->ffi = OnnxRuntimeFFI::get($libraryPath);
        $this->api = OnnxRuntimeFFI::getApi();
        $this->initializeEnvironment();
    }

    private function initializeEnvironment(): void
    {
        $envPtr = $this->ffi->new('void*[1]');
        $status = ($this->api->CreateEnv)(0, 'onnx-tts', $envPtr);

        if ($status !== null) {
            $this->handleError($status);
        }

        $this->env = $envPtr[0];
    }

    public function createSession(string $modelPath): OrtSession
    {
        $optionsPtr = $this->ffi->new('void*[1]');
        $status = ($this->api->CreateSessionOptions)($optionsPtr);

        if ($status !== null) {
            $this->handleError($status);
        }

        $options = $optionsPtr[0];

        $sessionPtr = $this->ffi->new('void*[1]');
        $status = ($this->api->CreateSession)(
            $this->env,
            $modelPath,
            $options,
            $sessionPtr
        );

        ($this->api->ReleaseSessionOptions)($options);

        if ($status !== null) {
            $this->handleError($status);
        }

        return new OrtSession($this->ffi, $this->api, $sessionPtr[0]);
    }

    public function getVersion(): string
    {
        $version = ($this->api->GetVersionString)();

        return FFI::string($version);
    }

    public function __destruct()
    {
        if ($this->env !== null) {
            ($this->api->ReleaseEnv)($this->env);
        }
    }

    private function handleError($status): void
    {
        $message = ($this->api->GetErrorMessage)($status);
        $errorMsg = FFI::string($message);
        ($this->api->ReleaseStatus)($status);
        throw new OnnxRuntimeException($errorMsg);
    }
}
