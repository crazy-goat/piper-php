<?php

declare(strict_types=1);

namespace OnnxTTS\FFI;

use FFI;
use OnnxTTS\Exception\LibraryNotFoundException;

class OnnxRuntimeFFI
{
    private static ?FFI $ffi = null;

    private const C_DEF = '
        typedef void* OrtEnv;
        typedef void* OrtSession;
        typedef void* OrtSessionOptions;
        typedef void* OrtRunOptions;
        typedef void* OrtValue;
        typedef void* OrtStatus;
        typedef void* OrtMemoryInfo;
        typedef void* OrtAllocator;
        
        typedef enum {
            ORT_OK = 0
        } OrtErrorCode;
        
        // Environment
        OrtStatus* OrtCreateEnv(int logging_level, const char* logid, OrtEnv** env);
        void OrtReleaseEnv(OrtEnv* env);
        
        // Session
        OrtStatus* OrtCreateSession(OrtEnv* env, const char* model_path, 
                                    OrtSessionOptions* options, OrtSession** session);
        void OrtReleaseSession(OrtSession* session);
        
        // Session options
        OrtStatus* OrtCreateSessionOptions(OrtSessionOptions** options);
        void OrtReleaseSessionOptions(OrtSessionOptions* options);
        
        // Run options
        OrtStatus* OrtCreateRunOptions(OrtRunOptions** options);
        void OrtReleaseRunOptions(OrtRunOptions* options);
        
        // Inference
        OrtStatus* OrtRun(OrtSession* session, OrtRunOptions* run_options,
                          const char* const* input_names, const OrtValue* const* inputs,
                          size_t input_count, const char* const* output_names,
                          size_t output_count, OrtValue** outputs);
        
        // Value creation
        OrtStatus* OrtCreateTensorWithDataAsOrtValue(OrtMemoryInfo* info, void* data,
                                                      size_t data_length, const int64_t* shape,
                                                      size_t shape_len, int type, OrtValue** value);
        OrtStatus* OrtGetTensorTypeAndShape(OrtValue* value, void** info);
        OrtStatus* OrtGetTensorMutableData(OrtValue* value, void** data);
        void OrtReleaseValue(OrtValue* value);
        
        // Memory info
        OrtStatus* OrtCreateCpuMemoryInfo(int allocator_type, int mem_type, OrtMemoryInfo** info);
        void OrtReleaseMemoryInfo(OrtMemoryInfo* info);
        
        // Error handling
        const char* OrtGetErrorMessage(OrtStatus* status);
        void OrtReleaseStatus(OrtStatus* status);
        
        // Version
        const char* OrtGetVersionString();
        
        // Session info
        OrtStatus* OrtSessionGetInputCount(OrtSession* session, size_t* count);
        OrtStatus* OrtSessionGetOutputCount(OrtSession* session, size_t* count);
        OrtStatus* OrtSessionGetInputName(OrtSession* session, size_t index, OrtAllocator* allocator, char** name);
        OrtStatus* OrtSessionGetOutputName(OrtSession* session, size_t index, OrtAllocator* allocator, char** name);
        OrtStatus* OrtSessionGetInputTypeInfo(OrtSession* session, size_t index, void** typeinfo);
        OrtStatus* OrtSessionGetOutputTypeInfo(OrtSession* session, size_t index, void** typeinfo);
    ';

    public static function get(string $libraryPath): FFI
    {
        if (self::$ffi === null) {
            if (!file_exists($libraryPath)) {
                throw new LibraryNotFoundException($libraryPath);
            }

            self::$ffi = FFI::cdef(self::C_DEF, $libraryPath);
        }

        return self::$ffi;
    }

    public static function reset(): void
    {
        self::$ffi = null;
    }
}
