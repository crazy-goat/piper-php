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

#ifndef PHP_ONNX_TTS_H
#define PHP_ONNX_TTS_H

extern zend_module_entry onnx_tts_module_entry;
#define phpext_onnx_tts_ptr &onnx_tts_module_entry

#define PHP_ONNX_TTS_VERSION "0.1.0"

#ifdef PHP_WIN32
#	define PHP_ONNX_TTS_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define PHP_ONNX_TTS_API __attribute__ ((visibility("default")))
#else
#	define PHP_ONNX_TTS_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINIT_FUNCTION(onnx_tts);
PHP_MINFO_FUNCTION(onnx_tts);

PHP_FUNCTION(onnx_tts_version);

#endif /* PHP_ONNX_TTS_H */
