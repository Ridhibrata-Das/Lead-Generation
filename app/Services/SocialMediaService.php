<?php

namespace App\Services;

use Config\SocialMedia;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SocialMediaService
{
    private Client $client;
    private SocialMedia $config;

    public function __construct()
    {
        $this->config = config('SocialMedia');
        $this->client = new Client();
    }

    // Facebook Methods
    public function getFacebookAuthUrl(): string
    {
        $params = [
            'client_id' => $this->config->facebookAppId,
            'redirect_uri' => $this->config->facebookRedirectUri,
            'scope' => implode(',', $this->config->facebookScopes),
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16))
        ];

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    public function getFacebookAccessToken(string $code): string
    {
        try {
            $response = $this->client->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'form_params' => [
                    'client_id' => $this->config->facebookAppId,
                    'client_secret' => $this->config->facebookAppSecret,
                    'redirect_uri' => $this->config->facebookRedirectUri,
                    'code' => $code
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get Facebook access token: ' . $e->getMessage());
        }
    }

    public function getFacebookProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get('https://graph.facebook.com/v18.0/me', [
                'query' => [
                    'fields' => 'id,name,email',
                    'access_token' => $accessToken
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get Facebook profile: ' . $e->getMessage());
        }
    }

    // Twitter Methods
    public function getTwitterAuthUrl(): string
    {
        try {
            // Get OAuth 2.0 token
            $response = $this->client->post('https://api.twitter.com/2/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->config->twitterApiKey . ':' . $this->config->twitterApiSecret)
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $params = [
                'response_type' => 'code',
                'client_id' => $this->config->twitterApiKey,
                'redirect_uri' => $this->config->twitterRedirectUri,
                'scope' => implode(' ', $this->config->twitterScopes),
                'state' => bin2hex(random_bytes(16)),
                'code_challenge' => 'challenge',
                'code_challenge_method' => 'plain'
            ];

            return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
        } catch (GuzzleException $e) {
            throw new Exception('Failed to initialize Twitter auth: ' . $e->getMessage());
        }
    }

    public function getTwitterAccessToken(string $code): string
    {
        try {
            $response = $this->client->post('https://api.twitter.com/2/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->config->twitterApiKey . ':' . $this->config->twitterApiSecret)
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->config->twitterRedirectUri,
                    'code_verifier' => 'challenge'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get Twitter access token: ' . $e->getMessage());
        }
    }

    public function getTwitterProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get('https://api.twitter.com/2/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'query' => [
                    'user.fields' => 'id,name,username,public_metrics'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get Twitter profile: ' . $e->getMessage());
        }
    }

    // LinkedIn Methods
    public function getLinkedinAuthUrl(): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config->linkedinClientId,
            'redirect_uri' => $this->config->linkedinRedirectUri,
            'scope' => implode(' ', $this->config->linkedinScopes),
            'state' => bin2hex(random_bytes(16))
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }

    public function getLinkedinAccessToken(string $code): array
    {
        try {
            $response = $this->client->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->config->linkedinClientId,
                    'client_secret' => $this->config->linkedinClientSecret,
                    'redirect_uri' => $this->config->linkedinRedirectUri
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get LinkedIn access token: ' . $e->getMessage());
        }
    }

    public function getLinkedinProfile(string $accessToken): array
    {
        try {
            // Get basic profile information
            $profileResponse = $this->client->get('https://api.linkedin.com/v2/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0'
                ]
            ]);

            $profile = json_decode($profileResponse->getBody()->getContents(), true);

            // Get email address
            $emailResponse = $this->client->get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0'
                ]
            ]);

            $emailData = json_decode($emailResponse->getBody()->getContents(), true);
            $email = $emailData['elements'][0]['handle~']['emailAddress'] ?? null;

            return array_merge($profile, ['email' => $email]);
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get LinkedIn profile: ' . $e->getMessage());
        }
    }
}
