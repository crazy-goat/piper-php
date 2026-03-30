/*
 * Minimal ONNX Runtime C Wrapper for PHP FFI
 * Only exports functions we actually need
 * 
 * API Table offsets (verified for ORT 1.16.3):
 * [0] CreateStatus
 * [1] GetErrorCode
 * [2] GetErrorMessage
 * [3] ReleaseStatus
 * [4] CreateEnv
 * [5] ReleaseEnv
 * [6] CreateSession
 * [7] ReleaseSession
 * [8] CreateSessionOptions
 * [9] ReleaseSessionOptions
 * [10] CreateRunOptions
 * [11] ReleaseRunOptions
 * [12] Run
 * [13] CreateCpuMemoryInfo
 * [14] ReleaseMemoryInfo
 * [15] CreateTensorWithDataAsOrtValue
 * [16] ReleaseValue
 * [17] GetVersionString
 * [18] SessionGetInputCount
 * [19] SessionGetOutputCount
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
typedef int OrtErrorCode;
typedef int OrtLoggingLevel;

/* API Base structure */
typedef struct OrtApiBase {
    const void* (*GetApi)(uint32_t version);
    const char* (*GetVersionString)(void);
} OrtApiBase;

/* Global state */
static void* g_handle = NULL;
static void** g_api_table = NULL;

/* Initialize and get API table */
static int init_ort() {
    if (g_api_table) return 0;
    
    if (!g_handle) {
        /* Try multiple paths */
        const char* paths[] = {
            "/home/decodo/.local/lib/libonnxruntime.so",
            "libonnxruntime.so",
            "libonnxruntime.so.1.21",
            "/lib/x86_64-linux-gnu/libonnxruntime.so.1.21",
            "/usr/local/lib/libonnxruntime.so",
            NULL
        };
        
        for (int i = 0; paths[i]; i++) {
            g_handle = dlopen(paths[i], RTLD_LAZY);
            if (g_handle) break;
        }
        
        if (!g_handle) return -1;
    }
    
    /* Get API base */
    const OrtApiBase* (*OrtGetApiBase)(void) = dlsym(g_handle, "OrtGetApiBase");
    if (!OrtGetApiBase) return -1;
    
    const OrtApiBase* base = OrtGetApiBase();
    if (!base) return -1;
    
    /* Get API - try version 16 first (for 1.16.x), then 21, then 1 */
    const void* api = base->GetApi(16);
    if (!api) {
        api = base->GetApi(21);
    }
    if (!api) {
        api = base->GetApi(1);
    }
    
    if (!api) return -1;
    
    g_api_table = (void**)api;
    return 0;
}

/* Get version string from base (not from API table) */
const char* ort_get_version() {
    if (!g_handle) {
        /* Just load to get base */
        const char* paths[] = {
            "/home/decodo/.local/lib/libonnxruntime.so",
            "libonnxruntime.so",
            NULL
        };
        
        for (int i = 0; paths[i]; i++) {
            g_handle = dlopen(paths[i], RTLD_LAZY);
            if (g_handle) break;
        }
        
        if (!g_handle) return NULL;
    }
    
    const OrtApiBase* (*OrtGetApiBase)(void) = dlsym(g_handle, "OrtGetApiBase");
    if (!OrtGetApiBase) return NULL;
    
    const OrtApiBase* base = OrtGetApiBase();
    if (!base) return NULL;
    
    return base->GetVersionString();
}

/* Create environment - offset 4 */
OrtStatus* ort_create_env(int logging_level, const char* logid, OrtEnv** env) {
    if (init_ort() != 0) return NULL;
    
    typedef OrtStatus* (*Func)(int, const char*, OrtEnv**);
    Func func = (Func)g_api_table[4];
    return func(logging_level, logid, env);
}

/* Release environment - offset 5 */
void ort_release_env(OrtEnv* env) {
    if (!g_api_table || !env) return;
    
    typedef void (*Func)(OrtEnv*);
    Func func = (Func)g_api_table[5];
    func(env);
}

/* Create session options - offset 8 */
OrtStatus* ort_create_session_options(OrtSessionOptions** options) {
    if (init_ort() != 0) return NULL;
    
    typedef OrtStatus* (*Func)(OrtSessionOptions**);
    Func func = (Func)g_api_table[8];
    return func(options);
}

/* Release session options - offset 9 */
void ort_release_session_options(OrtSessionOptions* options) {
    if (!g_api_table || !options) return;
    
    typedef void (*Func)(OrtSessionOptions*);
    Func func = (Func)g_api_table[9];
    func(options);
}

/* Create session - offset 6 */
OrtStatus* ort_create_session(OrtEnv* env, const char* model_path, OrtSessionOptions* options, OrtSession** session) {
    if (init_ort() != 0) return NULL;
    
    typedef OrtStatus* (*Func)(OrtEnv*, const char*, OrtSessionOptions*, OrtSession**);
    Func func = (Func)g_api_table[6];
    return func(env, model_path, options, session);
}

/* Release session - offset 7 */
void ort_release_session(OrtSession* session) {
    if (!g_api_table || !session) return;
    
    typedef void (*Func)(OrtSession*);
    Func func = (Func)g_api_table[7];
    func(session);
}

/* Get error message - offset 2 */
const char* ort_get_error_message(OrtStatus* status) {
    if (!g_api_table || !status) return NULL;
    
    typedef const char* (*Func)(OrtStatus*);
    Func func = (Func)g_api_table[2];
    return func(status);
}

/* Release status - offset 3 */
void ort_release_status(OrtStatus* status) {
    if (!g_api_table || !status) return;
    
    typedef void (*Func)(OrtStatus*);
    Func func = (Func)g_api_table[3];
    func(status);
}

/* Session get input count - offset 18 */
OrtStatus* ort_session_get_input_count(OrtSession* session, size_t* count) {
    if (init_ort() != 0) return NULL;
    
    typedef OrtStatus* (*Func)(OrtSession*, size_t*);
    Func func = (Func)g_api_table[18];
    return func(session, count);
}

/* Session get output count - offset 19 */
OrtStatus* ort_session_get_output_count(OrtSession* session, size_t* count) {
    if (init_ort() != 0) return NULL;
    
    typedef OrtStatus* (*Func)(OrtSession*, size_t*);
    Func func = (Func)g_api_table[19];
    return func(session, count);
}
