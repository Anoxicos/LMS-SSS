<?php

namespace App\Services\VoiceOver;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VoiceOverService
{
    public function generate(string $text, string $lang = 'en-us', ?string $voice = null): array
    {
        $provider = config('services.voice_over.provider', 'free_tts');

        return match ($provider) {
            'free_tts', 'voicerss' => $this->generateWithVoiceRss($text, $lang, $voice),
            'openai' => $this->generateWithOpenAi($text, $lang, $voice),
            'elevenlabs' => $this->generateWithElevenLabs($text, $lang, $voice),
            default => throw new RuntimeException('Unsupported voice over provider.'),
        };
    }

    private function generateWithVoiceRss(string $text, string $lang, ?string $voice = null): array
    {
        $apiUrl = config('services.voice_over.free_tts.url');
        $apiKey = config('services.voice_over.free_tts.key');

        if (!$apiUrl) {
            throw new RuntimeException('VoiceRSS API URL is not configured.');
        }

        if (!$apiKey) {
            throw new RuntimeException('VoiceRSS API key is not configured.');
        }

        $language = $this->normalizeVoiceRssLanguage($lang);

        $voice = $voice ?: config('services.voice_over.default_voice', 'default');

        $params = [
            'key' => $apiKey,
            'hl' => $language,
            'src' => $text,
            'c' => 'MP3',
            'f' => '44khz_16bit_stereo',
            'r' => '0',
        ];

        if (!empty($voice) && $voice !== 'default') {
            $params['v'] = $voice;
        }

        $response = Http::withOptions([
                'verify' => config('services.voice_over.free_tts.verify_ssl', true),
            ])
            ->timeout(120)
            ->get($apiUrl, $params);

        if (!$response->successful()) {
            throw new RuntimeException(
                'VoiceRSS API error. Status: ' .
                $response->status() .
                ' Body: ' .
                substr($response->body(), 0, 500)
            );
        }

        $body = $response->body();

        if (str_starts_with($body, 'ERROR')) {
            throw new RuntimeException('VoiceRSS error: ' . $body);
        }

        $contentType = $response->header('Content-Type') ?: 'audio/mpeg';

        return [
            'binary' => $body,
            'extension' => 'mp3',
            'mime' => $contentType,
            'provider' => 'voicerss',
            'voice' => $voice,
            'lang' => $language,
        ];
    }

    private function normalizeVoiceRssLanguage(?string $lang): string
    {
        return match (strtolower($lang ?: 'en-us')) {
            'en', 'en-us', 'english' => 'en-us',
            'en-gb' => 'en-gb',

            'fr', 'fr-fr', 'french' => 'fr-fr',

            'ar', 'ar-sa', 'arabic' => 'ar-sa',

            'es', 'es-es', 'spanish' => 'es-es',

            default => strtolower($lang ?: 'en-us'),
        };
    }

    private function generateWithOpenAi(string $text, string $lang, ?string $voice = null): array
    {
        throw new RuntimeException('OpenAI provider is not implemented yet.');
    }

    private function generateWithElevenLabs(string $text, string $lang, ?string $voice = null): array
    {
        throw new RuntimeException('ElevenLabs provider is not implemented yet.');
    }
}