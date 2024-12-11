<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table = 'leads';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'name', 'email', 'phone', 'source', 'source_id',
        'consent_given', 'consent_date', 'tags'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'required|valid_email|is_unique[leads.email,id,{id}]',
        'phone' => 'permit_empty|min_length[10]|max_length[20]',
        'consent_given' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'This email is already in our database.'
        ]
    ];

    public function addTags(int $leadId, array $tags): bool
    {
        $lead = $this->find($leadId);
        if (!$lead) {
            return false;
        }

        $currentTags = json_decode($lead['tags'] ?? '[]', true);
        $newTags = array_unique(array_merge($currentTags, $tags));
        
        return $this->update($leadId, [
            'tags' => json_encode($newTags)
        ]);
    }

    public function findBySource(string $source, ?string $sourceId = null)
    {
        $builder = $this->where('source', $source);
        if ($sourceId) {
            $builder->where('source_id', $sourceId);
        }
        return $builder->findAll();
    }
}
