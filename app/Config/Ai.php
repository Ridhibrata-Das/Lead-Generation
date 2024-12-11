<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ai extends BaseConfig
{
    /**
     * OpenAI API Configuration
     */
    public string $openAiApiKey = '';
    public string $openAiModel = 'gpt-4';
    public float $temperature = 0.7;
    public int $maxTokens = 500;

    /**
     * Platform-specific prompts
     */
    public array $platformPrompts = [
        'twitter' => "Create a concise, engaging tweet that's under 280 characters.",
        'linkedin' => "Write a professional and informative post suitable for LinkedIn's business audience.",
        'facebook' => "Create an engaging and conversational post that encourages community interaction.",
        'instagram' => "Write a casual, trendy caption that works well with visual content.",
    ];

    /**
     * Content type prompts
     */
    public array $contentTypePrompts = [
        'post' => "Create a social media post about:",
        'thread' => "Create a detailed thread discussing:",
        'caption' => "Write an engaging caption for an image about:",
        'hashtags' => "Generate relevant hashtags for content about:",
    ];
}
