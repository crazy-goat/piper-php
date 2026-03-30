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
            void* CreateStatus;
            void* GetErrorCode;
            void* GetErrorMessage;
            void* ReleaseStatus;
            void* CreateEnv;
            void* ReleaseEnv;
            void* CreateSession;
            void* ReleaseSession;
            void* CreateSessionOptions;
            void* ReleaseSessionOptions;
            void* CreateRunOptions;
            void* ReleaseRunOptions;
            void* Run;
            void* CreateCpuMemoryInfo;
            void* ReleaseMemoryInfo;
            void* CreateTensorWithDataAsOrtValue;
            void* ReleaseValue;
            void* GetVersionString;
            void* SessionGetInputCount;
            void* SessionGetOutputCount;
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
            self::$api = self::$ffi->cast('OrtApi*', $api);
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
