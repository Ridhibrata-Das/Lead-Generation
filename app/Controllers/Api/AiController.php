<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Services\AiService;
use Exception;

class AiController extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';
    protected $aiService;

    public function __construct()
    {
        $this->aiService = new AiService();
    }

    public function generate()
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $data = $this->request->getJSON(true);
            
            if (!isset($data['prompt']) || !isset($data['type'])) {
                return $this->failValidationError('Prompt and type are required');
            }

            $response = $this->aiService->generateContent(
                $data['prompt'],
                $data['type'],
                $data['platform'] ?? null
            );

            return $this->respond($response);
        } catch (Exception $e) {
            log_message('error', 'AI content generation failed: ' . $e->getMessage());
            return $this->failServerError('An error occurred while generating content: ' . $e->getMessage());
        }
    }
}
