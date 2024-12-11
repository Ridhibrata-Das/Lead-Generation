<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PostModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class PostsController extends ResourceController
{
    use ResponseTrait;

    protected $model;
    protected $format = 'json';

    public function __construct()
    {
        $this->model = new PostModel();
    }

    public function index()
    {
        try {
            // TODO: Get actual user ID from authentication
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $posts = $this->model->findByAuthor($userId);

            // Transform platforms from JSON string back to array
            $posts = array_map(function($post) {
                $post['platforms'] = json_decode($post['platforms'], true);
                return $post;
            }, $posts);

            return $this->respond($posts);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while fetching posts');
        }
    }

    public function show($id = null)
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $post = $this->model->find($id);

            if (!$post) {
                return $this->failNotFound('Post not found');
            }

            if ($post['author_id'] !== $userId) {
                return $this->failForbidden('You do not have permission to view this post');
            }

            $post['platforms'] = json_decode($post['platforms'], true);
            return $this->respond($post);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while fetching the post');
        }
    }

    public function create()
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $data = $this->request->getJSON(true);
            $data['author_id'] = $userId;

            if (!$this->model->insert($data)) {
                return $this->failValidationErrors($this->model->errors());
            }

            $post = $this->model->find($this->model->getInsertID());
            $post['platforms'] = json_decode($post['platforms'], true);

            return $this->respondCreated($post);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while creating the post');
        }
    }

    public function update($id = null)
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $post = $this->model->find($id);

            if (!$post) {
                return $this->failNotFound('Post not found');
            }

            if ($post['author_id'] !== $userId) {
                return $this->failForbidden('You do not have permission to update this post');
            }

            $data = $this->request->getJSON(true);
            
            if (!$this->model->update($id, $data)) {
                return $this->failValidationErrors($this->model->errors());
            }

            $updatedPost = $this->model->find($id);
            $updatedPost['platforms'] = json_decode($updatedPost['platforms'], true);

            return $this->respond($updatedPost);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while updating the post');
        }
    }

    public function delete($id = null)
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $post = $this->model->find($id);

            if (!$post) {
                return $this->failNotFound('Post not found');
            }

            if ($post['author_id'] !== $userId) {
                return $this->failForbidden('You do not have permission to delete this post');
            }

            if (!$this->model->delete($id)) {
                return $this->failServerError('Failed to delete the post');
            }

            return $this->respondDeleted(['id' => $id]);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while deleting the post');
        }
    }

    public function scheduled()
    {
        try {
            $userId = $this->request->getHeaderLine('X-User-Id');
            
            if (!$userId) {
                return $this->failUnauthorized('User not authenticated');
            }

            $posts = $this->model->findScheduled();
            
            // Filter for user's posts only
            $posts = array_filter($posts, function($post) use ($userId) {
                return $post['author_id'] === $userId;
            });

            // Transform platforms from JSON string back to array
            $posts = array_map(function($post) {
                $post['platforms'] = json_decode($post['platforms'], true);
                return $post;
            }, $posts);

            return $this->respond(array_values($posts));
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while fetching scheduled posts');
        }
    }
}
