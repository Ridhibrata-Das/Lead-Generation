<?php

namespace App\Services;

use Config\Ai;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AiService
{
    private Client $client;
    private Ai $config;
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->config = config('Ai');
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->openAiApiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function generateContent(string $prompt, string $type, ?string $platform = null): array
    {
        try {
            $fullPrompt = $this->buildPrompt($prompt, $type, $platform);
            $response = $this->client->post(self::OPENAI_API_URL, [
                'json' => [
                    'model' => $this->config->openAiModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional social media content creator.'],
                        ['role' => 'user', 'content' => $fullPrompt],
                    ],
                    'temperature' => $this->config->temperature,
                    'max_tokens' => $this->config->maxTokens,
                    'n' => 3, // Generate 3 alternatives
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Extract the generated content and format response
            $suggestions = array_map(function ($choice) {
                return trim($choice['message']['content']);
            }, $result['choices']);

            return [
                'content' => $suggestions[0], // Primary suggestion
                'suggestions' => array_slice($suggestions, 1), // Alternative suggestions
            ];
        } catch (GuzzleException | Exception $e) {
            throw new Exception('Failed to generate content: ' . $e->getMessage());
        }
    }

    private function buildPrompt(string $prompt, string $type, ?string $platform): string
    {
        $typePrompt = $this->config->contentTypePrompts[$type] ?? '';
        $platformPrompt = $platform ? ("\n" . ($this->config->platformPrompts[$platform] ?? '')) : '';
        
        return "{$typePrompt} {$prompt}{$platformPrompt}";
    }
}
