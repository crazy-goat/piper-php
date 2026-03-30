<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS;

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

        /** @var array<string, mixed> $config */
        $config = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);

        /** @var string $name */
        $name = $config['dataset'] ?? $key;
        /** @var array<string, mixed> $language */
        $language = $config['language'] ?? [];
        /** @var string $languageName */
        $languageName = $language['name_english'] ?? 'Unknown';
        /** @var string $languageCode */
        $languageCode = $language['code'] ?? '';
        /** @var array<string, mixed> $audio */
        $audio = $config['audio'] ?? [];
        /** @var string $quality */
        $quality = $audio['quality'] ?? 'unknown';
        /** @var int $numSpeakers */
        $numSpeakers = $config['num_speakers'] ?? 1;

        return new self(
            key: $key,
            name: $name,
            language: $languageName,
            languageCode: $languageCode,
            quality: $quality,
            numSpeakers: $numSpeakers,
        );
    }
}
