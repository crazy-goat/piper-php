/*
 * ONNX Runtime C Wrapper for PHP FFI
 * 
 * This wrapper provides flat C functions that call the ONNX Runtime API
 * through the vtable pattern, making it compatible with PHP FFI.
 */

#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <dlfcn.h>

/* ONNX Runtime types */
typedef void* OrtEnv;
typedef void* OrtSession;
typedef void* OrtSessionOptions;
typedef void* OrtRunOptions;
typedef void* OrtValue;
typedef void* OrtStatus;
typedef void* OrtMemoryInfo;
typedef void* OrtAllocator;

/* API Base structure */
typedef struct OrtApiBase {
    const char* (*GetVersionString)(void);
    const void* (*GetApi)(uint32_t version);
} OrtApiBase;

/* API function pointer types */
typedef OrtStatus* (*CreateEnvFunc)(int, const char*, OrtEnv**);
typedef void (*ReleaseEnvFunc)(OrtEnv*);
typedef OrtStatus* (*CreateSessionFunc)(OrtEnv*, const char*, OrtSessionOptions*, OrtSession**);
typedef void (*ReleaseSessionFunc)(OrtSession*);
typedef OrtStatus* (*CreateSessionOptionsFunc)(OrtSessionOptions**);
typedef void (*ReleaseSessionOptionsFunc)(OrtSessionOptions*);
typedef OrtStatus* (*CreateRunOptionsFunc)(OrtRunOptions**);
typedef void (*ReleaseRunOptionsFunc)(OrtRunOptions*);
typedef OrtStatus* (*RunFunc)(OrtSession*, OrtRunOptions*, const char**, OrtValue**, size_t, const char**, size_t, OrtValue**);
typedef OrtStatus* (*CreateCpuMemoryInfoFunc)(int, int, OrtMemoryInfo**);
typedef void (*ReleaseMemoryInfoFunc)(OrtMemoryInfo*);
typedef OrtStatus* (*CreateTensorWithDataAsOrtValueFunc)(OrtMemoryInfo*, void*, size_t, const int64_t*, size_t, int, OrtValue**);
typedef void (*ReleaseValueFunc)(OrtValue*);
typedef const char* (*GetErrorMessageFunc)(OrtStatus*);
typedef void (*ReleaseStatusFunc)(OrtStatus*);
typedef const char* (*GetVersionStringFunc)(void);
typedef OrtStatus* (*SessionGetInputCountFunc)(OrtSession*, size_t*);
typedef OrtStatus* (*SessionGetOutputCountFunc)(OrtSession*, size_t*);

/* API structure with function pointers */
typedef struct OrtApi {
    void* CreateStatus;
    void* GetErrorCode;
    GetErrorMessageFunc GetErrorMessage;
    ReleaseStatusFunc ReleaseStatus;
    CreateEnvFunc CreateEnv;
    ReleaseEnvFunc ReleaseEnv;
    CreateSessionFunc CreateSession;
    ReleaseSessionFunc ReleaseSession;
    CreateSessionOptionsFunc CreateSessionOptions;
    ReleaseSessionOptionsFunc ReleaseSessionOptions;
    CreateRunOptionsFunc CreateRunOptions;
    ReleaseRunOptionsFunc ReleaseRunOptions;
    RunFunc Run;
    CreateCpuMemoryInfoFunc CreateCpuMemoryInfo;
    ReleaseMemoryInfoFunc ReleaseMemoryInfo;
    CreateTensorWithDataAsOrtValueFunc CreateTensorWithDataAsOrtValue;
    ReleaseValueFunc ReleaseValue;
    GetVersionStringFunc GetVersionString;
    SessionGetInputCountFunc SessionGetInputCount;
    SessionGetOutputCountFunc SessionGetOutputCount;
} OrtApi;

/* Global API pointer */
static const OrtApi* g_api = NULL;
static void* g_handle = NULL;

/* Initialize the API */
static int init_api() {
    if (g_api) return 0;
    
    /* Try to load from common locations */
    const char* paths[] = {
        "libonnxruntime.so",
        "libonnxruntime.so.1.21",
        "libonnxruntime.so.1.21.0",
        "/home/decodo/.local/lib/libonnxruntime.so",
        "/lib/x86_64-linux-gnu/libonnxruntime.so.1.21",
        "/usr/local/lib/libonnxruntime.so",
        NULL
    };
    
    for (int i = 0; paths[i]; i++) {
        g_handle = dlopen(paths[i], RTLD_LAZY);
        if (g_handle) break;
    }
    
    if (!g_handle) {
        return -1;
    }
    
    const OrtApiBase* (*OrtGetApiBase)(void) = dlsym(g_handle, "OrtGetApiBase");
    if (!OrtGetApiBase) {
        dlclose(g_handle);
        g_handle = NULL;
        return -1;
    }
    
    const OrtApiBase* base = OrtGetApiBase();
    if (!base) {
        dlclose(g_handle);
        g_handle = NULL;
        return -1;
    }
    
    /* Try API version 21 first, then 16 */
    g_api = base->GetApi(21);
    if (!g_api) {
        g_api = base->GetApi(16);
    }
    if (!g_api) {
        g_api = base->GetApi(1);
    }
    
    if (!g_api) {
        dlclose(g_handle);
        g_handle = NULL;
        return -1;
    }
    
    return 0;
}

/* Cleanup */
static void cleanup_api() {
    if (g_handle) {
        dlclose(g_handle);
        g_handle = NULL;
        g_api = NULL;
    }
}

/* Wrapper functions */

const char* ort_get_version_string() {
    if (init_api() != 0) return NULL;
    return g_api->GetVersionString();
}

OrtStatus* ort_create_env(int logging_level, const char* logid, OrtEnv** env) {
    if (init_api() != 0) return NULL;
    return g_api->CreateEnv(logging_level, logid, env);
}

void ort_release_env(OrtEnv* env) {
    if (!g_api || !env) return;
    g_api->ReleaseEnv(env);
}

OrtStatus* ort_create_session_options(OrtSessionOptions** options) {
    if (init_api() != 0) return NULL;
    return g_api->CreateSessionOptions(options);
}

void ort_release_session_options(OrtSessionOptions* options) {
    if (!g_api || !options) return;
    g_api->ReleaseSessionOptions(options);
}

OrtStatus* ort_create_session(OrtEnv* env, const char* model_path, OrtSessionOptions* options, OrtSession** session) {
    if (init_api() != 0) return NULL;
    return g_api->CreateSession(env, model_path, options, session);
}

void ort_release_session(OrtSession* session) {
    if (!g_api || !session) return;
    g_api->ReleaseSession(session);
}

OrtStatus* ort_session_get_input_count(OrtSession* session, size_t* count) {
    if (init_api() != 0) return NULL;
    return g_api->SessionGetInputCount(session, count);
}

OrtStatus* ort_session_get_output_count(OrtSession* session, size_t* count) {
    if (init_api() != 0) return NULL;
    return g_api->SessionGetOutputCount(session, count);
}

const char* ort_get_error_message(OrtStatus* status) {
    if (!g_api || !status) return NULL;
    return g_api->GetErrorMessage(status);
}

void ort_release_status(OrtStatus* status) {
    if (!g_api || !status) return;
    g_api->ReleaseStatus(status);
}
