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

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(onnx_tts)
{
    if (init_ort_api() == FAILURE) {
        php_error_docref(NULL, E_WARNING, "Failed to initialize ONNX Runtime API");
        return FAILURE;
    }
    
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

/* {{{ onnx_tts_functions[] */
const zend_function_entry onnx_tts_functions[] = {
    PHP_FE(onnx_tts_version, NULL)
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
