/*
  +----------------------------------------------------------------------+
  | ONNX TTS Extension for PHP - Object Oriented API                     |
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

#ifndef PHP_ONNX_TTS_OO_H
#define PHP_ONNX_TTS_OO_H

#include "php_onnx_tts.h"
#include <onnxruntime_c_api.h>

/* Class entry pointers */
extern zend_class_entry *onnxruntime_ce;
extern zend_class_entry *onnxsession_ce;

/* OnnxRuntime object structure */
typedef struct _php_onnxruntime_object {
    OrtEnv *env;
    zend_object std;
} php_onnxruntime_object;

/* OnnxSession object structure */
typedef struct _php_onnxsession_object {
    OrtSession *session;
    zend_object std;
} php_onnxsession_object;

/* Helper macros */
#define Z_ONNXRUNTIME_P(zv) ((php_onnxruntime_object*)((char*)(Z_OBJ_P(zv)) - XtOffsetOf(php_onnxruntime_object, std)))
#define Z_ONNXSESSION_P(zv) ((php_onnxsession_object*)((char*)(Z_OBJ_P(zv)) - XtOffsetOf(php_onnxsession_object, std)))

/* Function declarations */
PHP_MINIT_FUNCTION(onnx_tts_oo);

#endif /* PHP_ONNX_TTS_OO_H */
