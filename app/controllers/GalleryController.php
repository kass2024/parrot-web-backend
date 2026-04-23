<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Gallery.php';

class GalleryController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireLogin();
    }

    public function index() {
        $this->setTitle('Gallery Management');
        
        $gallery_model = new Gallery();
        
        // Get pagination parameters
        $page = (int) $this->getInput('page', 1);
        $search = $this->getInput('search', '');
        $status = $this->getInput('status', '');
        
        // Build conditions
        $conditions = [];
        if ($status === 'active') {
            $conditions['is_active'] = 1;
        } elseif ($status === 'inactive') {
            $conditions['is_active'] = 0;
        }
        
        if (!empty($search)) {
            // Add search condition (you'd need to modify the model to support search)
        }
        
        // Get paginated results
        $result = $gallery_model->paginate($page, 12, $conditions, 'order_index ASC, created_at DESC');
        
        $this->view('gallery/index', [
            'gallery_images' => $result['data'],
            'pagination' => $result['pagination'],
            'search' => $search,
            'status' => $status,
            'csrf_token' => $this->generateCSRFToken(),
            'success' => $this->getFlashMessage('success'),
            'error' => $this->getFlashMessage('error')
        ]);
    }

    public function create() {
        $this->setTitle('Add Gallery Image');
        
        if ($this->isPost()) {
            $this->handleCreate();
        } else {
            $this->showCreateForm();
        }
    }

    private function showCreateForm() {
        $gallery_model = new Gallery();
        $next_order = $gallery_model->getMaxOrder() + 1;
        
        $this->view('gallery/create', [
            'next_order' => $next_order,
            'csrf_token' => $this->generateCSRFToken(),
            'errors' => $this->getFlashMessage('errors'),
            'old_input' => $this->getFlashMessage('old_input') ?? []
        ]);
    }

    private function handleCreate() {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('gallery/create');
        }

        $gallery_model = new Gallery();
        
        $data = [
            'title' => $this->getInput('title'),
            'description' => $this->getInput('description'),
            'order_index' => $this->getInput('order_index'),
            'is_active' => $this->getInput('is_active') === 'on' ? 1 : 0
        ];

        // Handle file upload
        $file = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
        }

        $result = $gallery_model->createGalleryImage($data, $file);
        
        if ($result['success']) {
            $this->logActivity('gallery_create', "Created gallery image: {$data['title']}");
            $this->setFlashMessage('success', 'Gallery image created successfully');
            $this->redirect('gallery');
        } else {
            $this->setFlashMessage('errors', $result['errors']);
            $this->setFlashMessage('old_input', $data);
            $this->redirect('gallery/create');
        }
    }

    public function edit($id) {
        $this->setTitle('Edit Gallery Image');
        
        $gallery_model = new Gallery();
        $image = $gallery_model->findById($id);
        
        if (!$image) {
            $this->setFlashMessage('error', 'Gallery image not found');
            $this->redirect('gallery');
        }
        
        if ($this->isPost()) {
            $this->handleEdit($id, $image);
        } else {
            $this->showEditForm($image);
        }
    }

    private function showEditForm($image) {
        $this->view('gallery/edit', [
            'image' => $image,
            'csrf_token' => $this->generateCSRFToken(),
            'errors' => $this->getFlashMessage('errors'),
            'old_input' => $this->getFlashMessage('old_input') ?? []
        ]);
    }

    private function handleEdit($id, $current_image) {
        if (!$this->validateCSRF()) {
            $this->setFlashMessage('error', 'Invalid request token');
            $this->redirect('gallery/edit/' . $id);
        }

        $gallery_model = new Gallery();
        
        $data = [
            'title' => $this->getInput('title'),
            'description' => $this->getInput('description'),
            'order_index' => $this->getInput('order_index'),
            'is_active' => $this->getInput('is_active') === 'on' ? 1 : 0
        ];

        // Handle file upload
        $file = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
        }

        $result = $gallery_model->updateGalleryImage($id, $data, $file);
        
        if ($result['success']) {
            $this->logActivity('gallery_update', "Updated gallery image: {$data['title']}");
            $this->setFlashMessage('success', 'Gallery image updated successfully');
            $this->redirect('gallery');
        } else {
            $this->setFlashMessage('errors', $result['errors']);
            $this->setFlashMessage('old_input', $data);
            $this->redirect('gallery/edit/' . $id);
        }
    }

    public function delete($id) {
        if ($this->isPost()) {
            $this->handleDelete($id);
        } else {
            $this->showDeleteConfirm($id);
        }
    }

    private function showDeleteConfirm($id) {
        $gallery_model = new Gallery();
        $image = $gallery_model->findById($id);
        
        if (!$image) {
            $this->setFlashMessage('error', 'Gallery image not found');
            $this->redirect('gallery');
        }
        
        $this->view('gallery/delete', [
            'image' => $image,
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    private function handleDelete($id) {
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request token']);
        }

        $gallery_model = new Gallery();
        $image = $gallery_model->findById($id);
        
        if (!$image) {
            $this->json(['success' => false, 'message' => 'Gallery image not found']);
        }

        $result = $gallery_model->deleteGalleryImage($id);
        
        if ($result['success']) {
            $this->logActivity('gallery_delete', "Deleted gallery image: {$image['title']}");
            $this->json(['success' => true, 'message' => 'Gallery image deleted successfully']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to delete gallery image', 'errors' => $result['errors']]);
        }
    }

    public function toggleStatus($id) {
        $this->validateAjax();
        
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request token']);
        }

        $gallery_model = new Gallery();
        $image = $gallery_model->findById($id);
        
        if (!$image) {
            $this->json(['success' => false, 'message' => 'Gallery image not found']);
        }

        $result = $gallery_model->toggleStatus($id);
        
        if ($result['success']) {
            $status_text = $result['status'] ? 'activated' : 'deactivated';
            $this->logActivity('gallery_toggle', "Toggled status for gallery image: {$image['title']} ({$status_text})");
            $this->json(['success' => true, 'message' => "Gallery image {$status_text} successfully", 'status' => $result['status']]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to toggle status']);
        }
    }

    public function reorder() {
        $this->validateAjax();
        
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request token']);
        }

        $orders = $this->getInput('orders');
        
        if (empty($orders) || !is_array($orders)) {
            $this->json(['success' => false, 'message' => 'Invalid order data']);
        }

        $gallery_model = new Gallery();
        $result = $gallery_model->reorderImages($orders);
        
        if ($result['success']) {
            $this->logActivity('gallery_reorder', 'Reordered gallery images');
            $this->json(['success' => true, 'message' => 'Gallery images reordered successfully']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reorder images']);
        }
    }

    public function viewImage($id) {
        $gallery_model = new Gallery();
        $image = $gallery_model->findById($id);
        
        if (!$image) {
            $this->setFlashMessage('error', 'Gallery image not found');
            $this->redirect('gallery');
        }
        
        $this->view('gallery/view', [
            'image' => $image
        ]);
    }

    public function bulkActions() {
        $this->validateAjax();
        
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request token']);
        }

        $action = $this->getInput('action');
        $ids = $this->getInput('ids');
        
        if (empty($action) || empty($ids) || !is_array($ids)) {
            $this->json(['success' => false, 'message' => 'Invalid action or IDs']);
        }

        $gallery_model = new Gallery();
        $success_count = 0;
        
        foreach ($ids as $id) {
            $image = $gallery_model->findById($id);
            if (!$image) continue;

            switch ($action) {
                case 'activate':
                    if ($gallery_model->update($id, ['is_active' => 1])) {
                        $success_count++;
                    }
                    break;
                case 'deactivate':
                    if ($gallery_model->update($id, ['is_active' => 0])) {
                        $success_count++;
                    }
                    break;
                case 'delete':
                    $result = $gallery_model->deleteGalleryImage($id);
                    if ($result['success']) {
                        $success_count++;
                    }
                    break;
            }
        }

        $this->logActivity('gallery_bulk', "Performed bulk action: {$action} on {$success_count} images");
        $this->json(['success' => true, 'message' => "Action performed on {$success_count} images successfully"]);
    }

    public function upload() {
        $this->validateAjax();
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'No file uploaded or upload error']);
        }

        $gallery_model = new Gallery();
        
        $data = [
            'title' => pathinfo($_FILES['file']['name'], PATHINFO_FILENAME),
            'description' => '',
            'order_index' => $gallery_model->getMaxOrder() + 1,
            'is_active' => 1
        ];

        $result = $gallery_model->createGalleryImage($data, $_FILES['file']);
        
        if ($result['success']) {
            $this->logActivity('gallery_upload', "Uploaded gallery image via drag & drop: {$data['title']}");
            $this->json(['success' => true, 'message' => 'Image uploaded successfully', 'image_id' => $result['gallery_id']]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to upload image', 'errors' => $result['errors']]);
        }
    }
}
?>
