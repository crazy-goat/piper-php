<?php

declare(strict_types=1);

namespace Decodo\PiperTTS;

final readonly class VoiceInfo
{
    public function __construct(
        public string $key,
        public string $name,
        public string $language,
        public string $languageCode,
        public string $quality,
        public int $numSpeakers,
    ) {
    }

    /**
     * Parse a voice config JSON file into a VoiceInfo.
     *
     * @param string $key   Voice key derived from filename (e.g. "pl_PL-gosia-medium")
     * @param string $jsonPath  Absolute path to the .onnx.json file
     */
    public static function fromConfigFile(string $key, string $jsonPath): self
    {
        $raw = file_get_contents($jsonPath);
        if ($raw === false) {
            throw new Exception\PiperException("Cannot read voice config: {$jsonPath}");
        }

        $config = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);

        return new self(
            key: $key,
            name: $config['dataset'] ?? $key,
            language: $config['language']['name_english'] ?? 'Unknown',
            languageCode: $config['language']['code'] ?? '',
            quality: $config['audio']['quality'] ?? 'unknown',
            numSpeakers: $config['num_speakers'] ?? 1,
        );
    }
}
