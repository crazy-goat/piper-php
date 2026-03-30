<?php

declare(strict_types=1);

namespace OnnxTTS\FFI;

use FFI;
use OnnxTTS\Exception\LibraryNotFoundException;

class OnnxRuntimeFFI
{
    private static ?FFI $ffi = null;
    private static $api = null;

    private const C_DEF = '
        typedef struct OrtApiBase {
            const char* (*GetVersionString)(void);
            const void* (*GetApi)(uint32_t version);
        } OrtApiBase;
        
        const OrtApiBase* OrtGetApiBase(void);
    ';

    private const ORT_API_DEF = '
        typedef struct OrtApi {
            void* (*CreateEnv)(int logging_level, const char* logid, void** env);
            void (*ReleaseEnv)(void* env);
            void* (*CreateSession)(void* env, const char* model_path, void* options, void** session);
            void (*ReleaseSession)(void* session);
            void* (*CreateSessionOptions)(void** options);
            void (*ReleaseSessionOptions)(void* options);
            void* (*CreateRunOptions)(void** options);
            void (*ReleaseRunOptions)(void* options);
            void* (*Run)(void* session, void* run_options, const char** input_names, 
                         void** inputs, size_t input_count, const char** output_names,
                         size_t output_count, void** outputs);
            void* (*CreateCpuMemoryInfo)(int allocator_type, int mem_type, void** info);
            void (*ReleaseMemoryInfo)(void* info);
            void* (*CreateTensorWithDataAsOrtValue)(void* info, void* data, size_t data_length,
                                                     const int64_t* shape, size_t shape_len, 
                                                     int type, void** value);
            void (*ReleaseValue)(void* value);
            const char* (*GetErrorMessage)(void* status);
            void (*ReleaseStatus)(void* status);
            const char* (*GetVersionString)(void);
            void* (*SessionGetInputCount)(void* session, size_t* count);
            void* (*SessionGetOutputCount)(void* session, size_t* count);
        } OrtApi;
    ';

    public static function get(string $libraryPath): FFI
    {
        if (self::$ffi === null) {
            if (!file_exists($libraryPath)) {
                throw new LibraryNotFoundException($libraryPath);
            }

            // Load base API
            $baseFfi = FFI::cdef(self::C_DEF, $libraryPath);
            $apiBase = $baseFfi->OrtGetApiBase();
            $api = $apiBase->GetApi(16); // ORT_API_VERSION = 16

            // Cast to OrtApi struct
            self::$ffi = FFI::cdef(self::ORT_API_DEF, $libraryPath);
            self::$api = $api;
        }

        return self::$ffi;
    }

    public static function getApi()
    {
        return self::$api;
    }

    public static function reset(): void
    {
        self::$ffi = null;
        self::$api = null;
    }
}
