/*
 * ONNX Runtime C Wrapper using system headers
 * Links with libonnxruntime.so from system
 */

#include <onnxruntime/onnxruntime_c_api.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

/* Global API pointer */
static const OrtApi* g_api = NULL;

/* Initialize API */
static int init_api() {
    if (g_api) return 0;
    
    const OrtApiBase* base = OrtGetApiBase();
    if (!base) return -1;
    
    /* Try API version 21 first, then 16, then 1 */
    g_api = base->GetApi(21);
    if (!g_api) {
        g_api = base->GetApi(16);
    }
    if (!g_api) {
        g_api = base->GetApi(1);
    }
    
    return g_api ? 0 : -1;
}

/* Get version string */
const char* ort_get_version() {
    return OrtGetApiBase()->GetVersionString();
}

/* Create environment */
OrtStatus* ort_create_env(int logging_level, const char* logid, OrtEnv** env) {
    if (init_api() != 0) return NULL;
    return g_api->CreateEnv(logging_level, logid, env);
}

/* Release environment */
void ort_release_env(OrtEnv* env) {
    if (!g_api || !env) return;
    g_api->ReleaseEnv(env);
}

/* Create session options */
OrtStatus* ort_create_session_options(OrtSessionOptions** options) {
    if (init_api() != 0) return NULL;
    return g_api->CreateSessionOptions(options);
}

/* Release session options */
void ort_release_session_options(OrtSessionOptions* options) {
    if (!g_api || !options) return;
    g_api->ReleaseSessionOptions(options);
}

/* Create session */
OrtStatus* ort_create_session(OrtEnv* env, const char* model_path, OrtSessionOptions* options, OrtSession** session) {
    if (init_api() != 0) return NULL;
    return g_api->CreateSession(env, model_path, options, session);
}

/* Release session */
void ort_release_session(OrtSession* session) {
    if (!g_api || !session) return;
    g_api->ReleaseSession(session);
}

/* Get error message */
const char* ort_get_error_message(OrtStatus* status) {
    if (!g_api || !status) return NULL;
    return g_api->GetErrorMessage(status);
}

/* Release status */
void ort_release_status(OrtStatus* status) {
    if (!g_api || !status) return;
    g_api->ReleaseStatus(status);
}

/* Session get input count */
OrtStatus* ort_session_get_input_count(OrtSession* session, size_t* count) {
    if (init_api() != 0) return NULL;
    return g_api->SessionGetInputCount(session, count);
}

/* Session get output count */
OrtStatus* ort_session_get_output_count(OrtSession* session, size_t* count) {
    if (init_api() != 0) return NULL;
    return g_api->SessionGetOutputCount(session, count);
}
