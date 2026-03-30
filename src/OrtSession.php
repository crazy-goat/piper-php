<?php

declare(strict_types=1);

namespace OnnxTTS;

use FFI;
use OnnxTTS\Exception\InferenceException;

/**
 * ONNX Runtime Session wrapper
 * 
 * Represents an inference session with a loaded model.
 */
class OrtSession
{
    private FFI $ffi;
    private $session;
    private ?array $inputNames = null;
    private ?array $outputNames = null;
    
    /**
     * Constructor
     * 
     * @param FFI $ffi FFI instance
     * @param mixed $session Session pointer from ORT
     */
    public function __construct(FFI $ffi, $session)
    {
        $this->ffi = $ffi;
        $this->session = $session;
    }
    
    /**
     * Get input tensor names
     * 
     * @return array Array of input names
     */
    public function getInputNames(): array
    {
        if ($this->inputNames === null) {
            $this->inputNames = $this->fetchNames('input');
        }
        return $this->inputNames;
    }
    
    /**
     * Get output tensor names
     * 
     * @return array Array of output names
     */
    public function getOutputNames(): array
    {
        if ($this->outputNames === null) {
            $this->outputNames = $this->fetchNames('output');
        }
        return $this->outputNames;
    }
    
    /**
     * Fetch input/output names from session
     * 
     * @param string $type 'input' or 'output'
     * @return array Array of names
     */
    private function fetchNames(string $type): array
    {
        $countPtr = $this->ffi->new('size_t[1]');
        $method = $type === 'input' ? 'ort_session_get_input_count' : 'ort_session_get_output_count';
        
        $status = $this->ffi->{$method}($this->session, $countPtr);
        if ($status !== null) {
            $this->handleError($status);
        }
        
        $count = (int) $countPtr[0];
        $names = [];
        
        for ($i = 0; $i < $count; $i++) {
            $names[] = $type . '_' . $i;
        }
        
        return $names;
    }
    
    /**
     * Run inference
     * 
     * @param array $inputs Input tensors
     * @return array Output tensors
     */
    public function run(array $inputs): array
    {
        // Simplified implementation - full implementation would:
        // 1. Create input tensors from PHP arrays
        // 2. Allocate output tensors
        // 3. Call ort_run
        // 4. Extract output data
        // 5. Release tensors
        
        // For now, return empty array
        return [];
    }
    
    /**
     * Destructor - cleanup session
     */
    public function __destruct()
    {
        if ($this->session !== null) {
            $this->ffi->ort_release_session($this->session);
        }
    }
    
    /**
     * Handle error status
     * 
     * @param mixed $status Error status pointer
     * @throws InferenceException Always throws with error message
     */
    private function handleError($status): void
    {
        $message = $this->ffi->ort_get_error_message($status);
        $errorMsg = $message;
        $this->ffi->ort_release_status($status);
        throw new InferenceException($errorMsg);
    }
}
