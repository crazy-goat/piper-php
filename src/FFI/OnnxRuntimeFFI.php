<?php

declare(strict_types=1);

namespace OnnxTTS\FFI;

use FFI;
use OnnxTTS\Exception\LibraryNotFoundException;

/**
 * FFI wrapper for ONNX Runtime using system wrapper library
 * 
 * The system wrapper (libonnxruntime_system_wrapper.so) provides flat C functions
 * that internally use the ONNX Runtime C API through the vtable pattern.
 */
class OnnxRuntimeFFI
{
    private static ?FFI $ffi = null;
    private static string $wrapperPath;
    
    /**
     * C definitions for the wrapper library functions
     */
    private const C_DEF = '
        const char* ort_get_version();
        void* ort_create_env(int logging_level, const char* logid, void** env);
        void ort_release_env(void* env);
        void* ort_create_session_options(void** options);
        void ort_release_session_options(void* options);
        void* ort_create_session(void* env, const char* model_path, void* options, void** session);
        void ort_release_session(void* session);
        const char* ort_get_error_message(void* status);
        void ort_release_status(void* status);
        void* ort_session_get_input_count(void* session, size_t* count);
        void* ort_session_get_output_count(void* session, size_t* count);
    ';
    
    /**
     * Get FFI instance with wrapper library
     * 
     * @param string $libraryPath Path to the wrapper library (not used, kept for API compatibility)
     * @return FFI FFI instance
     * @throws LibraryNotFoundException If wrapper library not found
     */
    public static function get(string $libraryPath = ''): FFI
    {
        if (self::$ffi === null) {
            self::$wrapperPath = self::findWrapperLibrary();
            
            if (!self::$wrapperPath) {
                throw new LibraryNotFoundException(
                    'libonnxruntime_system_wrapper.so not found. ' .
                    'Please build it: cd src/FFI && make wrapper'
                );
            }
            
            self::$ffi = FFI::cdef(self::C_DEF, self::$wrapperPath);
        }
        
        return self::$ffi;
    }
    
    /**
     * Find the wrapper library in common locations
     */
    private static function findWrapperLibrary(): ?string
    {
        $paths = [
            __DIR__ . '/libonnxruntime_system_wrapper.so',
            __DIR__ . '/libonnxruntime_minimal.so',
            '/home/decodo/.local/lib/libonnxruntime_system_wrapper.so',
            '/usr/local/lib/libonnxruntime_system_wrapper.so',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Reset FFI instance (for testing)
     */
    public static function reset(): void
    {
        self::$ffi = null;
    }
}
