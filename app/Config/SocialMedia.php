<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SocialMedia extends BaseConfig
{
    // LinkedIn Configuration
    public string $linkedinClientId = '';
    public string $linkedinClientSecret = '';
    public string $linkedinRedirectUri = '';
    public array $linkedinScopes = ['r_emailaddress', 'r_liteprofile'];

    // Facebook Configuration
    public string $facebookAppId = '';
    public string $facebookAppSecret = '';
    public string $facebookRedirectUri = '';
    public array $facebookScopes = ['email', 'public_profile'];

    // Twitter Configuration
    public string $twitterApiKey = '';
    public string $twitterApiSecret = '';
    public string $twitterRedirectUri = '';
    public string $twitterBearerToken = '';
    public array $twitterScopes = ['users.read', 'tweet.read'];

    // Common Configuration
    public string $baseUrl = '';  // Your application's base URL

    public function __construct()
    {
        parent::__construct();
        
        // Set redirect URIs based on base URL
        $this->linkedinRedirectUri = $this->baseUrl . '/api/leads/linkedin-callback';
        $this->facebookRedirectUri = $this->baseUrl . '/api/leads/facebook-callback';
        $this->twitterRedirectUri = $this->baseUrl . '/api/leads/twitter-callback';
    }
}
