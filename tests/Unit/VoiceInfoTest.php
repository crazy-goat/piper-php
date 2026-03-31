<?php

declare(strict_types=1);

namespace CrazyGoat\PiperTTS\Tests\Unit;

use CrazyGoat\PiperTTS\VoiceInfo;
use PHPUnit\Framework\TestCase;

final class VoiceInfoTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../fixtures';
    }

    public function test_parses_config_file(): void
    {
        $configPath = $this->fixturesDir . '/voice-config.json';
        $voice = VoiceInfo::fromConfigFile('pl_PL-gosia-medium', $configPath);

        self::assertSame('pl_PL-gosia-medium', $voice->key);
        self::assertSame('gosia', $voice->name);
        self::assertSame('Polish', $voice->language);
        self::assertSame('pl_PL', $voice->languageCode);
        self::assertSame('medium', $voice->quality);
        self::assertSame(1, $voice->numSpeakers);
    }

    public function test_uses_key_as_name_when_dataset_missing(): void
    {
        $configPath = $this->fixturesDir . '/voice-config-no-dataset.json';
        $config = ['language' => ['name_english' => 'English', 'code' => 'en_US'], 'audio' => ['quality' => 'low'], 'num_speakers' => 1];
        file_put_contents($configPath, json_encode($config));

        $voice = VoiceInfo::fromConfigFile('en_US-test-low', $configPath);

        self::assertSame('en_US-test-low', $voice->name);

        unlink($configPath);
    }

    public function test_handles_missing_language_fields(): void
    {
        $configPath = $this->fixturesDir . '/voice-config-no-lang.json';
        $config = ['dataset' => 'test', 'audio' => ['quality' => 'low'], 'num_speakers' => 1];
        file_put_contents($configPath, json_encode($config));

        $voice = VoiceInfo::fromConfigFile('test-voice', $configPath);

        self::assertSame('Unknown', $voice->language);
        self::assertSame('', $voice->languageCode);

        unlink($configPath);
    }

    public function test_throws_on_missing_file(): void
    {
        $this->expectException(\CrazyGoat\PiperTTS\Exception\PiperException::class);

        VoiceInfo::fromConfigFile('nonexistent', '/nonexistent/path.json');
    }
}
