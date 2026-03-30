<?php

declare(strict_types=1);

/**
 * FFI (Foreign Function Interface) allows calling C functions from PHP.
 * 
 * This stub includes both standard FFI methods and Piper-specific C API methods
 * that are loaded via FFI::cdef() from libpiper.so.
 */
class FFI
{
    /**
     * Load shared library with given flags.
     * 
     * @param string $filename Path to shared library
     * @param int $flags RTLD_LAZY=1, RTLD_NOW=2, RTLD_GLOBAL=256, etc.
     * @return \FFI\CData|null Library handle or null on error
     */
    public static function dlopen(string $filename, int $flags): ?\FFI\CData {}
    
    /**
     * Get error message from last dlopen/dlclose call.
     * 
     * @return string|null Error message or null if no error
     */
    public static function dlerror(): ?string {}
    
    /**
     * Create FFI object from C definitions.
     * 
     * @param string $code C definitions (structs, functions, etc.)
     * @param string|null $lib Path to shared library (optional)
     * @return \FFI FFI object with bound functions
     */
    public static function cdef(string $code, ?string $lib = null): \FFI {}
    
    /**
     * Check if C data pointer is null.
     * 
     * @param \FFI\CData $ptr C data pointer
     * @return bool True if pointer is null
     */
    public static function isNull(\FFI\CData $ptr): bool {}
    
    /**
     * Get address of C data.
     * 
     * @param \FFI\CData $ptr C data
     * @return \FFI\CData Pointer to the data
     */
    public static function addr(\FFI\CData $ptr): \FFI\CData {}
    
    /**
     * Allocate new C data of given type.
     * 
     * @param string $type C type name
     * @return \FFI\CData Allocated C data
     */
    public static function new(string $type): \FFI\CData {}
    
    /**
     * Convert C string to PHP string.
     * 
     * @param \FFI\CData $ptr C string pointer
     * @return string PHP string
     */
    public static function string(\FFI\CData $ptr): string {}
    
    /**
     * Cast C data to different type.
     * 
     * @param string $type Target C type
     * @param \FFI\CData $ptr C data to cast
     * @return \FFI\CData|null Casted data or null
     */
    public static function cast(string $type, \FFI\CData $ptr): ?\FFI\CData {}
    
    // Piper C API methods - loaded via FFI::cdef()
    
    /**
     * Create a new Piper synthesizer instance.
     * 
     * @param string $model_path Path to ONNX model file
     * @param string $config_path Path to model config JSON file
     * @param string $espeak_data_path Path to espeak-ng-data directory
     * @return \FFI\CData|null Synthesizer handle or null on error
     */
    public function piper_create(string $model_path, string $config_path, string $espeak_data_path): ?\FFI\CData {}
    
    /**
     * Free a Piper synthesizer instance.
     * 
     * @param \FFI\CData $synth Synthesizer handle
     * @return void
     */
    public function piper_free(\FFI\CData $synth): void {}
    
    /**
     * Get default synthesis options.
     * 
     * @param \FFI\CData $synth Synthesizer handle
     * @return \FFI\CData Options struct with defaults
     */
    public function piper_default_synthesize_options(\FFI\CData $synth): \FFI\CData {}
    
    /**
     * Start text synthesis.
     * 
     * @param \FFI\CData $synth Synthesizer handle
     * @param string $text Text to synthesize
     * @param \FFI\CData $options Synthesis options
     * @return int 0 on success, non-zero on error
     */
    public function piper_synthesize_start(\FFI\CData $synth, string $text, \FFI\CData $options): int {}
    
    /**
     * Get next audio chunk from synthesis.
     * 
     * @param \FFI\CData $synth Synthesizer handle
     * @param \FFI\CData $chunk Audio chunk struct to fill
     * @return int 0=more chunks, 1=last chunk, -1=error
     */
    public function piper_synthesize_next(\FFI\CData $synth, \FFI\CData $chunk): int {}
}
