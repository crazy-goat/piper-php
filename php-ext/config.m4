dnl config.m4 for extension onnx_tts

PHP_ARG_WITH([onnx_tts],
  [for onnx_tts support],
  [AS_HELP_STRING([--with-onnx_tts],
    [Include ONNX TTS support])])

if test "$PHP_ONNX_TTS" != "no"; then
  dnl Check for ONNX Runtime library
  AC_MSG_CHECKING([for onnxruntime library])
  
  for i in /usr/lib/x86_64-linux-gnu /usr/local/lib /opt/onnxruntime/lib /home/decodo/.local/lib; do
    if test -r $i/libonnxruntime.so -o -r $i/libonnxruntime.so.1.21; then
      ONNXRUNTIME_LIB_DIR=$i
      break
    fi
  done
  
  if test -z "$ONNXRUNTIME_LIB_DIR"; then
    AC_MSG_RESULT([not found])
    AC_MSG_ERROR([ONNX Runtime library not found. Install libonnxruntime-dev or set path])
  fi
  
  PHP_ADD_LIBRARY_WITH_PATH(onnxruntime, $ONNXRUNTIME_LIB_DIR, ONNX_TTS_SHARED_LIBADD)
  AC_MSG_RESULT([found in $ONNXRUNTIME_LIB_DIR])
  
  dnl Check for header
  AC_MSG_CHECKING([for onnxruntime_c_api.h])
  for i in /usr/include/onnxruntime /usr/local/include/onnxruntime /usr/include; do
    if test -r $i/onnxruntime_c_api.h; then
      ONNXRUNTIME_INC=$i
      break
    fi
  done
  
  if test -z "$ONNXRUNTIME_INC"; then
    AC_MSG_RESULT([not found])
    AC_MSG_ERROR([ONNX Runtime headers not found. Install libonnxruntime-dev])
  fi
  
  PHP_ADD_INCLUDE($ONNXRUNTIME_INC)
  AC_MSG_RESULT([found in $ONNXRUNTIME_INC])
  
  PHP_SUBST(ONNX_TTS_SHARED_LIBADD)
  
  PHP_NEW_EXTENSION(onnx_tts, onnx_tts.c, $ext_shared)
fi
