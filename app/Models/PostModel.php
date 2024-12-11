<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table = 'posts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false; // We'll use UUID
    protected $returnType = 'array';
    protected $allowedFields = [
        'id',
        'title',
        'content',
        'author_id',
        'created_at',
        'scheduled_for',
        'platforms',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'content' => 'required',
        'author_id' => 'required',
        'platforms' => 'required',
        'status' => 'required|in_list[draft,scheduled,published]'
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'Title is required',
            'min_length' => 'Title must be at least 3 characters long',
            'max_length' => 'Title cannot exceed 255 characters'
        ],
        'content' => [
            'required' => 'Content is required'
        ],
        'platforms' => [
            'required' => 'At least one platform must be selected'
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Invalid status value'
        ]
    ];

    protected $beforeInsert = ['generateUuid', 'preparePlatforms'];
    protected $beforeUpdate = ['preparePlatforms'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['id'])) {
            $data['data']['id'] = bin2hex(random_bytes(16));
        }
        return $data;
    }

    protected function preparePlatforms(array $data)
    {
        if (isset($data['data']['platforms']) && is_array($data['data']['platforms'])) {
            $data['data']['platforms'] = json_encode($data['data']['platforms']);
        }
        return $data;
    }

    public function findByAuthor($authorId)
    {
        return $this->where('author_id', $authorId)->findAll();
    }

    public function findScheduled()
    {
        return $this->where('status', 'scheduled')
                    ->where('scheduled_for >', date('Y-m-d H:i:s'))
                    ->orderBy('scheduled_for', 'ASC')
                    ->findAll();
    }
}
