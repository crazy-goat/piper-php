/*
  +----------------------------------------------------------------------+
  | ONNX TTS Extension for PHP                                          |
  +----------------------------------------------------------------------+
  | Copyright (c) 2025                                                   |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to            |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Your Name                                                    |
  +----------------------------------------------------------------------+
*/

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_onnx_tts.h"
#include <onnxruntime_c_api.h>

/* Global API pointer */
static const OrtApi* g_ort_api = NULL;

/* Session resource type */
static int le_onnx_session;

/* Initialize ONNX Runtime API */
static int init_ort_api() {
    if (g_ort_api) {
        return SUCCESS;
    }
    
    const OrtApiBase* base = OrtGetApiBase();
    if (!base) {
        return FAILURE;
    }
    
    /* Try API version 21 first, then 16, then 1 */
    g_ort_api = base->GetApi(21);
    if (!g_ort_api) {
        g_ort_api = base->GetApi(16);
    }
    if (!g_ort_api) {
        g_ort_api = base->GetApi(1);
    }
    
    return g_ort_api ? SUCCESS : FAILURE;
}

/* Resource destructor for session */
static void php_onnx_session_dtor(zend_resource *rsrc)
{
    OrtSession *session = (OrtSession*)rsrc->ptr;
    if (session && g_ort_api) {
        g_ort_api->ReleaseSession(session);
    }
}

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(onnx_tts)
{
    if (init_ort_api() == FAILURE) {
        php_error_docref(NULL, E_WARNING, "Failed to initialize ONNX Runtime API");
        return FAILURE;
    }
    
    /* Register resource type for sessions */
    le_onnx_session = zend_register_list_destructors_ex(
        php_onnx_session_dtor, 
        NULL, 
        "onnx_session", 
        module_number
    );
    
    return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(onnx_tts)
{
    const OrtApiBase* base = OrtGetApiBase();
    const char* version = base ? base->GetVersionString() : "unknown";
    
    php_info_print_table_start();
    php_info_print_table_header(2, "ONNX TTS support", "enabled");
    php_info_print_table_row(2, "ONNX Runtime Version", version);
    php_info_print_table_end();
}
/* }}} */

/* {{{ proto string onnx_tts_version()
   Get ONNX Runtime version string */
ZEND_BEGIN_ARG_INFO(arginfo_onnx_tts_version, 0)
ZEND_END_ARG_INFO()

PHP_FUNCTION(onnx_tts_version)
{
    const OrtApiBase* base = OrtGetApiBase();
    if (base) {
        RETURN_STRING(base->GetVersionString());
    }
    RETURN_FALSE;
}
/* }}} */

/* {{{ proto resource onnx_tts_load_model(string model_path)
   Load ONNX model and return session resource */
ZEND_BEGIN_ARG_INFO(arginfo_onnx_tts_load_model, 0)
    ZEND_ARG_INFO(0, model_path)
ZEND_END_ARG_INFO()

PHP_FUNCTION(onnx_tts_load_model)
{
    char *model_path;
    size_t model_path_len;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s", &model_path, &model_path_len) == FAILURE) {
        RETURN_FALSE;
    }
    
    if (!g_ort_api) {
        php_error_docref(NULL, E_WARNING, "ONNX Runtime not initialized");
        RETURN_FALSE;
    }
    
    /* Create environment (simplified - using default) */
    OrtEnv *env = NULL;
    OrtStatus *status = g_ort_api->CreateEnv(ORT_LOGGING_LEVEL_WARNING, "onnx_tts", &env);
    if (status != NULL) {
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to create environment: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Create session options */
    OrtSessionOptions *options = NULL;
    status = g_ort_api->CreateSessionOptions(&options);
    if (status != NULL) {
        g_ort_api->ReleaseEnv(env);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to create session options: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Create session */
    OrtSession *session = NULL;
    status = g_ort_api->CreateSession(env, model_path, options, &session);
    
    /* Cleanup options (session keeps reference to env) */
    g_ort_api->ReleaseSessionOptions(options);
    
    if (status != NULL) {
        g_ort_api->ReleaseEnv(env);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to load model: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Return as resource */
    RETURN_RES(zend_register_resource(session, le_onnx_session));
}
/* }}} */

/* {{{ proto array onnx_tts_get_model_info(resource session)
   Get model input/output info */
ZEND_BEGIN_ARG_INFO(arginfo_onnx_tts_get_model_info, 0)
    ZEND_ARG_INFO(0, session)
ZEND_END_ARG_INFO()

PHP_FUNCTION(onnx_tts_get_model_info)
{
    zval *session_zval;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "r", &session_zval) == FAILURE) {
        RETURN_FALSE;
    }
    
    OrtSession *session = (OrtSession*)zend_fetch_resource(Z_RES_P(session_zval), "onnx_session", le_onnx_session);
    if (!session) {
        php_error_docref(NULL, E_WARNING, "Invalid session resource");
        RETURN_FALSE;
    }
    
    /* Get input count */
    size_t input_count = 0;
    OrtStatus *status = g_ort_api->SessionGetInputCount(session, &input_count);
    if (status != NULL) {
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get input count: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Get output count */
    size_t output_count = 0;
    status = g_ort_api->SessionGetOutputCount(session, &output_count);
    if (status != NULL) {
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get output count: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Return array with info */
    array_init(return_value);
    add_assoc_long(return_value, "input_count", input_count);
    add_assoc_long(return_value, "output_count", output_count);
}
/* }}} */

/* {{{ proto array onnx_tts_run(resource session, array input_data, array shape = [1, count])
   Run inference on the model */
ZEND_BEGIN_ARG_INFO_EX(arginfo_onnx_tts_run, 0, 0, 2)
    ZEND_ARG_INFO(0, session)
    ZEND_ARG_ARRAY_INFO(0, input_data, 0)
    ZEND_ARG_ARRAY_INFO(0, shape, 1)
ZEND_END_ARG_INFO()

PHP_FUNCTION(onnx_tts_run)
{
    zval *session_zval;
    zval *input_array;
    zval *shape_array = NULL;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "ra|a", &session_zval, &input_array, &shape_array) == FAILURE) {
        RETURN_FALSE;
    }
    
    OrtSession *session = (OrtSession*)zend_fetch_resource(Z_RES_P(session_zval), "onnx_session", le_onnx_session);
    if (!session) {
        php_error_docref(NULL, E_WARNING, "Invalid session resource");
        RETURN_FALSE;
    }
    
    /* Get input info */
    size_t input_count = 0;
    OrtStatus *status = g_ort_api->SessionGetInputCount(session, &input_count);
    if (status != NULL || input_count == 0) {
        if (status) g_ort_api->ReleaseStatus(status);
        php_error_docref(NULL, E_WARNING, "Model has no inputs");
        RETURN_FALSE;
    }
    
    /* Get output count */
    size_t output_count = 0;
    status = g_ort_api->SessionGetOutputCount(session, &output_count);
    if (status != NULL || output_count == 0) {
        if (status) g_ort_api->ReleaseStatus(status);
        php_error_docref(NULL, E_WARNING, "Model has no outputs");
        RETURN_FALSE;
    }
    
    /* Count input array elements */
    uint32_t num_input_values = zend_hash_num_elements(Z_ARRVAL_P(input_array));
    if (num_input_values == 0) {
        php_error_docref(NULL, E_WARNING, "Input array is empty");
        RETURN_FALSE;
    }
    
    /* Determine shape */
    int64_t *input_shape;
    size_t num_dims;
    
    if (shape_array && zend_hash_num_elements(Z_ARRVAL_P(shape_array)) > 0) {
        /* Use provided shape */
        num_dims = zend_hash_num_elements(Z_ARRVAL_P(shape_array));
        input_shape = (int64_t*)emalloc(num_dims * sizeof(int64_t));
        
        zval *val;
        size_t i = 0;
        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(shape_array), val) {
            if (Z_TYPE_P(val) == IS_LONG) {
                input_shape[i++] = Z_LVAL_P(val);
            } else {
                input_shape[i++] = 0;
            }
        } ZEND_HASH_FOREACH_END();
    } else {
        /* Default shape: [1, num_input_values] */
        num_dims = 2;
        input_shape = (int64_t*)emalloc(2 * sizeof(int64_t));
        input_shape[0] = 1;
        input_shape[1] = num_input_values;
    }
    
    /* Allocate input data buffer */
    float *input_data = (float*)emalloc(num_input_values * sizeof(float));
    if (!input_data) {
        php_error_docref(NULL, E_WARNING, "Failed to allocate input buffer");
        RETURN_FALSE;
    }
    
    /* Copy PHP array to C buffer */
    uint32_t i = 0;
    zval *val;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(input_array), val) {
        if (Z_TYPE_P(val) == IS_LONG) {
            input_data[i++] = (float)Z_LVAL_P(val);
        } else if (Z_TYPE_P(val) == IS_DOUBLE) {
            input_data[i++] = (float)Z_DVAL_P(val);
        } else {
            input_data[i++] = 0.0f;
        }
    } ZEND_HASH_FOREACH_END();
    
    /* Create memory info for CPU */
    OrtMemoryInfo *memory_info = NULL;
    status = g_ort_api->CreateCpuMemoryInfo(OrtArenaAllocator, OrtMemTypeDefault, &memory_info);
    if (status != NULL) {
        efree(input_data);
        efree(input_shape);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to create memory info: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Create input tensor with dynamic shape */
    OrtValue *input_tensor = NULL;
    status = g_ort_api->CreateTensorWithDataAsOrtValue(
        memory_info, 
        input_data, 
        num_input_values * sizeof(float), 
        input_shape, 
        num_dims, 
        ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT, 
        &input_tensor
    );
    
    efree(input_shape);
    g_ort_api->ReleaseMemoryInfo(memory_info);
    
    if (status != NULL) {
        efree(input_data);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to create input tensor: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Prepare for run - get input/output names */
    OrtAllocator *allocator = NULL;
    status = g_ort_api->GetAllocatorWithDefaultOptions(&allocator);
    if (status != NULL) {
        efree(input_data);
        g_ort_api->ReleaseValue(input_tensor);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get allocator: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Get input name */
    char *input_name = NULL;
    status = g_ort_api->SessionGetInputName(session, 0, allocator, &input_name);
    if (status != NULL) {
        efree(input_data);
        g_ort_api->ReleaseValue(input_tensor);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get input name: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Get output name */
    char *output_name = NULL;
    status = g_ort_api->SessionGetOutputName(session, 0, allocator, &output_name);
    if (status != NULL) {
        efree(input_data);
        g_ort_api->ReleaseValue(input_tensor);
        allocator->Free(allocator, input_name);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get output name: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Run inference */
    const char *input_names[] = {input_name};
    const char *output_names[] = {output_name};
    OrtValue *input_tensors[] = {input_tensor};
    OrtValue *output_tensor = NULL;
    
    status = g_ort_api->Run(
        session,
        NULL,  /* run options */
        input_names,
        (const OrtValue* const*)input_tensors,
        1,  /* input count */
        output_names,
        1,  /* output count */
        &output_tensor
    );
    
    /* Cleanup input resources */
    efree(input_data);
    g_ort_api->ReleaseValue(input_tensor);
    allocator->Free(allocator, input_name);
    allocator->Free(allocator, output_name);
    
    if (status != NULL) {
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Inference failed: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Extract output data */
    float *output_data = NULL;
    status = g_ort_api->GetTensorMutableData(output_tensor, (void**)&output_data);
    if (status != NULL) {
        g_ort_api->ReleaseValue(output_tensor);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get output data: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Get output tensor info to determine size */
    OrtTensorTypeAndShapeInfo *output_info = NULL;
    status = g_ort_api->GetTensorTypeAndShape(output_tensor, &output_info);
    if (status != NULL) {
        g_ort_api->ReleaseValue(output_tensor);
        const char *msg = g_ort_api->GetErrorMessage(status);
        php_error_docref(NULL, E_WARNING, "Failed to get output info: %s", msg);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Get tensor shape */
    size_t output_num_dims = 0;
    status = g_ort_api->GetDimensionsCount(output_info, &output_num_dims);
    if (status != NULL) {
        g_ort_api->ReleaseTensorTypeAndShapeInfo(output_info);
        g_ort_api->ReleaseValue(output_tensor);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    int64_t *dims = (int64_t*)emalloc(output_num_dims * sizeof(int64_t));
    status = g_ort_api->GetDimensions(output_info, dims, output_num_dims);
    g_ort_api->ReleaseTensorTypeAndShapeInfo(output_info);
    if (status != NULL) {
        efree(dims);
        g_ort_api->ReleaseValue(output_tensor);
        g_ort_api->ReleaseStatus(status);
        RETURN_FALSE;
    }
    
    /* Calculate total output size */
    size_t output_size = 1;
    for (size_t j = 0; j < output_num_dims; j++) {
        output_size *= dims[j];
    }
    efree(dims);
    
    /* Create PHP array with output data */
    array_init(return_value);
    for (size_t j = 0; j < output_size; j++) {
        add_next_index_double(return_value, output_data[j]);
    }
    
    /* Cleanup output tensor */
    g_ort_api->ReleaseValue(output_tensor);
}
/* }}} */

/* {{{ proto bool onnx_tts_save_wav(string filename, array audio_data, int sample_rate)
   Save audio data as WAV file */
ZEND_BEGIN_ARG_INFO(arginfo_onnx_tts_save_wav, 0)
    ZEND_ARG_INFO(0, filename)
    ZEND_ARG_ARRAY_INFO(0, audio_data, 0)
    ZEND_ARG_INFO(0, sample_rate)
ZEND_END_ARG_INFO()

PHP_FUNCTION(onnx_tts_save_wav)
{
    char *filename;
    size_t filename_len;
    zval *audio_array;
    zend_long sample_rate = 24000;  // Default 24kHz
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "sa|l", &filename, &filename_len, &audio_array, &sample_rate) == FAILURE) {
        RETURN_FALSE;
    }
    
    /* Count audio samples */
    uint32_t num_samples = zend_hash_num_elements(Z_ARRVAL_P(audio_array));
    if (num_samples == 0) {
        php_error_docref(NULL, E_WARNING, "Audio array is empty");
        RETURN_FALSE;
    }
    
    /* Open file for writing */
    FILE *fp = fopen(filename, "wb");
    if (!fp) {
        php_error_docref(NULL, E_WARNING, "Failed to open file for writing: %s", filename);
        RETURN_FALSE;
    }
    
    /* WAV header constants */
    uint16_t audio_format = 1;      // PCM
    uint16_t num_channels = 1;    // Mono
    uint16_t bits_per_sample = 16; // 16-bit
    uint32_t byte_rate = sample_rate * num_channels * bits_per_sample / 8;
    uint16_t block_align = num_channels * bits_per_sample / 8;
    uint32_t data_size = num_samples * num_channels * bits_per_sample / 8;
    uint32_t file_size = 36 + data_size;
    
    /* Write WAV header */
    fwrite("RIFF", 1, 4, fp);                    // Chunk ID
    fwrite(&file_size, 4, 1, fp);                // Chunk size
    fwrite("WAVE", 1, 4, fp);                    // Format
    fwrite("fmt ", 1, 4, fp);                    // Subchunk1 ID
    uint32_t subchunk1_size = 16;
    fwrite(&subchunk1_size, 4, 1, fp);          // Subchunk1 size
    fwrite(&audio_format, 2, 1, fp);            // Audio format
    fwrite(&num_channels, 2, 1, fp);            // Number of channels
    fwrite(&sample_rate, 4, 1, fp);             // Sample rate
    fwrite(&byte_rate, 4, 1, fp);               // Byte rate
    fwrite(&block_align, 2, 1, fp);              // Block align
    fwrite(&bits_per_sample, 2, 1, fp);          // Bits per sample
    fwrite("data", 1, 4, fp);                    // Subchunk2 ID
    fwrite(&data_size, 4, 1, fp);              // Subchunk2 size
    
    /* Convert float audio data to 16-bit PCM and write */
    zval *val;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(audio_array), val) {
        float sample = 0.0f;
        if (Z_TYPE_P(val) == IS_LONG) {
            sample = (float)Z_LVAL_P(val);
        } else if (Z_TYPE_P(val) == IS_DOUBLE) {
            sample = (float)Z_DVAL_P(val);
        }
        
        /* Clamp to [-1.0, 1.0] */
        if (sample > 1.0f) sample = 1.0f;
        if (sample < -1.0f) sample = -1.0f;
        
        /* Convert to 16-bit signed integer */
        int16_t pcm_sample = (int16_t)(sample * 32767.0f);
        
        /* Write as little-endian */
        fwrite(&pcm_sample, 2, 1, fp);
    } ZEND_HASH_FOREACH_END();
    
    fclose(fp);
    
    RETURN_TRUE;
}
/* }}} */

/* {{{ onnx_tts_functions[] */
const zend_function_entry onnx_tts_functions[] = {
    PHP_FE(onnx_tts_version, arginfo_onnx_tts_version)
    PHP_FE(onnx_tts_load_model, arginfo_onnx_tts_load_model)
    PHP_FE(onnx_tts_get_model_info, arginfo_onnx_tts_get_model_info)
    PHP_FE(onnx_tts_run, arginfo_onnx_tts_run)
    PHP_FE(onnx_tts_save_wav, arginfo_onnx_tts_save_wav)
    PHP_FE_END
};
/* }}} */

/* {{{ onnx_tts_module_entry */
zend_module_entry onnx_tts_module_entry = {
    STANDARD_MODULE_HEADER,
    "onnx_tts",
    onnx_tts_functions,
    PHP_MINIT(onnx_tts),
    NULL,
    NULL,
    NULL,
    PHP_MINFO(onnx_tts),
    PHP_ONNX_TTS_VERSION,
    STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_ONNX_TTS
ZEND_GET_MODULE(onnx_tts)
#endif
