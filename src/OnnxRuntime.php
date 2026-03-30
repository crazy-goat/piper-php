<?php

declare(strict_types=1);

namespace OnnxTTS;

use FFI;
use OnnxTTS\Exception\OnnxRuntimeException;
use OnnxTTS\FFI\OnnxRuntimeFFI;

/**
 * ONNX Runtime wrapper for PHP
 * 
 * Provides a PHP-friendly interface to ONNX Runtime through FFI.
 * Uses a C wrapper library that handles the vtable API internally.
 */
class OnnxRuntime
{
    private FFI $ffi;
    private $env;
    
    /**
     * Constructor
     * 
     * @param string $libraryPath Kept for API compatibility, actual library path is handled by wrapper
     */
    public function __construct(string $libraryPath = '')
    {
        $this->ffi = OnnxRuntimeFFI::get($libraryPath);
        $this->initializeEnvironment();
    }
    
    /**
     * Initialize ONNX Runtime environment
     */
    private function initializeEnvironment(): void
    {
        $envPtr = $this->ffi->new('void*[1]');
        $status = $this->ffi->ort_create_env(0, 'onnx-tts', $envPtr);
        
        if ($status !== null) {
            $this->handleError($status);
        }
        
        $this->env = $envPtr[0];
    }
    
    /**
     * Create inference session from model file
     * 
     * @param string $modelPath Path to ONNX model file
     * @return OrtSession Session object
     * @throws OnnxRuntimeException If session creation fails
     */
    public function createSession(string $modelPath): OrtSession
    {
        $optionsPtr = $this->ffi->new('void*[1]');
        $status = $this->ffi->ort_create_session_options($optionsPtr);
        
        if ($status !== null) {
            $this->handleError($status);
        }
        
        $options = $optionsPtr[0];
        
        $sessionPtr = $this->ffi->new('void*[1]');
        $status = $this->ffi->ort_create_session(
            $this->env,
            $modelPath,
            $options,
            $sessionPtr
        );
        
        $this->ffi->ort_release_session_options($options);
        
        if ($status !== null) {
            $this->handleError($status);
        }
        
        return new OrtSession($this->ffi, $sessionPtr[0]);
    }
    
    /**
     * Get ONNX Runtime version string
     * 
     * @return string Version string (e.g., "1.21.0")
     */
    public function getVersion(): string
    {
        return $this->ffi->ort_get_version();
    }
    
    /**
     * Destructor - cleanup environment
     */
    public function __destruct()
    {
        if ($this->env !== null) {
            $this->ffi->ort_release_env($this->env);
        }
    }
    
    /**
     * Handle error status from ONNX Runtime
     * 
     * @param mixed $status Error status pointer
     * @throws OnnxRuntimeException Always throws with error message
     */
    private function handleError($status): void
    {
        $message = $this->ffi->ort_get_error_message($status);
        $errorMsg = $message;
        $this->ffi->ort_release_status($status);
        throw new OnnxRuntimeException($errorMsg);
    }
}
