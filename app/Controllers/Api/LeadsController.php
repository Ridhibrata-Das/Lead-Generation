<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\LeadModel;
use App\Services\SocialMediaService;
use Exception;

class LeadsController extends ResourceController
{
    use ResponseTrait;

    protected $model;
    protected $socialMediaService;
    protected $format = 'json';

    public function __construct()
    {
        $this->model = new LeadModel();
        $this->socialMediaService = new SocialMediaService();
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Ensure consent is given
            if (!isset($data['consent_given']) || !$data['consent_given']) {
                return $this->failValidationError('Consent must be given to collect data');
            }

            $data['consent_date'] = date('Y-m-d H:i:s');
            $data['source'] = $data['source'] ?? 'form';

            if (!$this->model->insert($data)) {
                return $this->failValidationError($this->model->errors());
            }

            return $this->respondCreated([
                'message' => 'Lead created successfully',
                'id' => $this->model->getInsertID()
            ]);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while creating the lead');
        }
    }

    public function linkedinCallback()
    {
        try {
            $code = $this->request->getVar('code');
            if (!$code) {
                return $this->failValidationError('Authorization code is required');
            }

            // Exchange code for access token
            $tokenData = $this->socialMediaService->getLinkedinAccessToken($code);
            
            // Get profile information
            $profile = $this->socialMediaService->getLinkedinProfile($tokenData['access_token']);

            // Create or update lead
            $leadData = [
                'name' => $profile['localizedFirstName'] . ' ' . $profile['localizedLastName'],
                'email' => $profile['email'] ?? null,
                'source' => 'linkedin',
                'source_id' => $profile['id'],
                'consent_given' => true,
                'consent_date' => date('Y-m-d H:i:s')
            ];

            // Check if lead exists
            $existingLead = $this->model->where('source', 'linkedin')
                                      ->where('source_id', $profile['id'])
                                      ->first();

            if ($existingLead) {
                $this->model->update($existingLead['id'], $leadData);
                $leadId = $existingLead['id'];
            } else {
                $this->model->insert($leadData);
                $leadId = $this->model->getInsertID();
            }

            return $this->respond([
                'message' => 'LinkedIn profile synchronized successfully',
                'id' => $leadId
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Failed to process LinkedIn callback: ' . $e->getMessage());
        }
    }

    public function facebookCallback()
    {
        try {
            $code = $this->request->getVar('code');
            if (!$code) {
                return $this->failValidationError('Authorization code is required');
            }

            // Exchange code for access token
            $accessToken = $this->socialMediaService->getFacebookAccessToken($code);
            
            // Get profile information
            $profile = $this->socialMediaService->getFacebookProfile($accessToken);

            // Create or update lead
            $leadData = [
                'name' => $profile['name'],
                'email' => $profile['email'] ?? null,
                'source' => 'facebook',
                'source_id' => $profile['id'],
                'consent_given' => true,
                'consent_date' => date('Y-m-d H:i:s')
            ];

            // Check if lead exists
            $existingLead = $this->model->where('source', 'facebook')
                                      ->where('source_id', $profile['id'])
                                      ->first();

            if ($existingLead) {
                $this->model->update($existingLead['id'], $leadData);
                $leadId = $existingLead['id'];
            } else {
                $this->model->insert($leadData);
                $leadId = $this->model->getInsertID();
            }

            return $this->respond([
                'message' => 'Facebook profile synchronized successfully',
                'id' => $leadId
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Failed to process Facebook callback: ' . $e->getMessage());
        }
    }

    public function twitterCallback()
    {
        try {
            $code = $this->request->getVar('code');
            if (!$code) {
                return $this->failValidationError('Authorization code is required');
            }

            // Exchange code for access token
            $accessToken = $this->socialMediaService->getTwitterAccessToken($code);
            
            // Get profile information
            $profile = $this->socialMediaService->getTwitterProfile($accessToken);

            // Create or update lead
            $leadData = [
                'name' => $profile['data']['name'],
                'source' => 'twitter',
                'source_id' => $profile['data']['id'],
                'consent_given' => true,
                'consent_date' => date('Y-m-d H:i:s')
            ];

            // Check if lead exists
            $existingLead = $this->model->where('source', 'twitter')
                                      ->where('source_id', $profile['data']['id'])
                                      ->first();

            if ($existingLead) {
                $this->model->update($existingLead['id'], $leadData);
                $leadId = $existingLead['id'];
            } else {
                $this->model->insert($leadData);
                $leadId = $this->model->getInsertID();
            }

            return $this->respond([
                'message' => 'Twitter profile synchronized successfully',
                'id' => $leadId
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Failed to process Twitter callback: ' . $e->getMessage());
        }
    }

    public function getSocialAuthUrls()
    {
        try {
            return $this->respond([
                'linkedin' => $this->socialMediaService->getLinkedinAuthUrl(),
                'facebook' => $this->socialMediaService->getFacebookAuthUrl(),
                'twitter' => $this->socialMediaService->getTwitterAuthUrl()
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Failed to generate auth URLs: ' . $e->getMessage());
        }
    }

    private function getLinkedinAccessToken(string $code): string
    {
        // Implementation for LinkedIn OAuth token exchange
        // You'll need to add your LinkedIn API credentials
        throw new Exception('LinkedIn integration not configured');
    }

    private function getLinkedinProfile(string $accessToken): array
    {
        // Implementation for fetching LinkedIn profile data
        // You'll need to implement the actual API calls
        throw new Exception('LinkedIn integration not configured');
    }

    public function import()
    {
        try {
            $file = $this->request->getFile('csv');
            
            if (!$file->isValid() || $file->getExtension() !== 'csv') {
                return $this->failValidationError('Please upload a valid CSV file');
            }

            $handle = fopen($file->getTempName(), 'r');
            $headers = fgetcsv($handle);
            $imported = 0;
            $failed = 0;

            while (($data = fgetcsv($handle)) !== false) {
                $leadData = array_combine($headers, $data);
                $leadData['source'] = 'form';
                $leadData['consent_given'] = true;
                $leadData['consent_date'] = date('Y-m-d H:i:s');

                if ($this->model->insert($leadData)) {
                    $imported++;
                } else {
                    $failed++;
                }
            }

            fclose($handle);

            return $this->respond([
                'message' => 'Import completed',
                'imported' => $imported,
                'failed' => $failed
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Failed to import leads: ' . $e->getMessage());
        }
    }
}
