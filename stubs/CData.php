<?php

declare(strict_types=1);

namespace FFI;

/**
 * CData represents data in C memory.
 * 
 * Properties depend on the underlying C struct.
 * For piper_audio_chunk:
 * @property ?float $samples Pointer to float array
 * @property int $num_samples Number of samples
 * @property int $sample_rate Sample rate in Hz
 * @property bool $is_last Whether this is the last chunk
 * @property ?int $phonemes Pointer to phoneme array
 * @property int $num_phonemes Number of phonemes
 * @property ?int $phoneme_ids Pointer to phoneme ID array
 * @property int $num_phoneme_ids Number of phoneme IDs
 * @property ?int $alignments Pointer to alignments array
 * @property int $num_alignments Number of alignments
 * 
 * For piper_synthesize_options:
 * @property int $speaker_id Speaker ID
 * @property float $length_scale Length scale (speed control)
 * @property float $noise_scale Noise scale
 * @property float $noise_w_scale Noise W scale
 */
class CData
{
}
