<?php

require_once __DIR__ . '/BaseModel.php';

class Gallery extends BaseModel {
    protected $table = 'gallery_images';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title', 'description', 'image_url', 'thumbnail_url', 'order_index', 'is_active'
    ];

    public function createGalleryImage($data, $file = null) {
        $validation_rules = [
            'title' => 'required|max:200',
            'description' => 'max:1000',
            'order_index' => 'integer|min:0'
        ];

        $errors = $this->validate($data, $validation_rules);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Handle file upload
        if ($file && isset($file['name']) && !empty($file['name'])) {
            $upload_result = handleFileUpload($file, UPLOAD_PATH . 'gallery/', ['jpg', 'jpeg', 'png', 'gif']);
            
            if (!$upload_result['success']) {
                return ['success' => false, 'errors' => ['image' => [$upload_result['message']]]];
            }
            
            $data['image_url'] = $upload_result['filename'];
            
            // Create thumbnail (basic implementation)
            $this->createThumbnail($upload_result['filename']);
        } elseif (empty($data['image_url'])) {
            return ['success' => false, 'errors' => ['image' => ['Image is required']]];
        }

        // Set default order if not provided
        if (!isset($data['order_index']) || $data['order_index'] === '') {
            $max_order = $this->getMaxOrder();
            $data['order_index'] = $max_order + 1;
        }

        $gallery_data = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'image_url' => $data['image_url'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'order_index' => $data['order_index'],
            'is_active' => $data['is_active'] ?? 1
        ];

        $gallery_id = $this->create($gallery_data);
        
        if ($gallery_id) {
            return ['success' => true, 'gallery_id' => $gallery_id];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to create gallery image']]];
    }

    public function updateGalleryImage($id, $data, $file = null) {
        $gallery = $this->findById($id);
        if (!$gallery) {
            return ['success' => false, 'errors' => ['general' => ['Gallery image not found']]];
        }

        $validation_rules = [
            'title' => 'required|max:200',
            'description' => 'max:1000',
            'order_index' => 'integer|min:0'
        ];

        $errors = $this->validate($data, $validation_rules);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Handle file upload
        if ($file && isset($file['name']) && !empty($file['name'])) {
            $upload_result = handleFileUpload($file, UPLOAD_PATH . 'gallery/', ['jpg', 'jpeg', 'png', 'gif']);
            
            if (!$upload_result['success']) {
                return ['success' => false, 'errors' => ['image' => [$upload_result['message']]]];
            }
            
            // Delete old image
            if (!empty($gallery['image_url'])) {
                $this->deleteImage($gallery['image_url']);
            }
            
            $data['image_url'] = $upload_result['filename'];
            $this->createThumbnail($upload_result['filename']);
        }

        $gallery_data = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'order_index' => $data['order_index'],
            'is_active' => $data['is_active'] ?? $gallery['is_active']
        ];

        if (isset($data['image_url'])) {
            $gallery_data['image_url'] = $data['image_url'];
        }

        if ($this->update($id, $gallery_data)) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update gallery image']]];
    }

    public function deleteGalleryImage($id) {
        $gallery = $this->findById($id);
        if (!$gallery) {
            return ['success' => false, 'errors' => ['general' => ['Gallery image not found']]];
        }

        // Delete image files
        if (!empty($gallery['image_url'])) {
            $this->deleteImage($gallery['image_url']);
        }
        
        if (!empty($gallery['thumbnail_url'])) {
            $this->deleteImage($gallery['thumbnail_url']);
        }

        if ($this->delete($id)) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to delete gallery image']]];
    }

    public function toggleStatus($id) {
        $gallery = $this->findById($id);
        if (!$gallery) {
            return ['success' => false, 'errors' => ['general' => ['Gallery image not found']]];
        }

        $new_status = $gallery['is_active'] ? 0 : 1;
        
        if ($this->update($id, ['is_active' => $new_status])) {
            return ['success' => true, 'status' => $new_status];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update status']]];
    }

    public function reorderImages($orders) {
        $this->beginTransaction();
        
        try {
            foreach ($orders as $order) {
                $this->update($order['id'], ['order_index' => $order['order_index']]);
            }
            
            $this->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->rollback();
            return ['success' => false, 'errors' => ['general' => ['Failed to reorder images']]];
        }
    }

    public function getActiveImages() {
        return $this->getAll(['is_active' => 1], 'order_index ASC');
    }

    public function getMaxOrder() {
        $sql = "SELECT MAX(order_index) as max_order FROM {$this->table}";
        $result = $this->queryOne($sql);
        return (int) ($result['max_order'] ?? 0);
    }

    private function createThumbnail($filename) {
        $source_path = UPLOAD_PATH . 'gallery/' . $filename;
        $thumb_path = UPLOAD_PATH . 'gallery/thumbs/' . $filename;
        
        if (!is_dir(UPLOAD_PATH . 'gallery/thumbs/')) {
            mkdir(UPLOAD_PATH . 'gallery/thumbs/', 0755, true);
        }
        
        // Basic thumbnail creation using GD
        if (file_exists($source_path)) {
            $image_info = getimagesize($source_path);
            if ($image_info) {
                $width = $image_info[0];
                $height = $image_info[1];
                $thumb_width = 300;
                $thumb_height = ($height / $width) * $thumb_width;
                
                $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
                
                switch ($image_info[2]) {
                    case IMAGETYPE_JPEG:
                        $source = imagecreatefromjpeg($source_path);
                        break;
                    case IMAGETYPE_PNG:
                        $source = imagecreatefrompng($source_path);
                        break;
                    case IMAGETYPE_GIF:
                        $source = imagecreatefromgif($source_path);
                        break;
                    default:
                        return false;
                }
                
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
                
                switch ($image_info[2]) {
                    case IMAGETYPE_JPEG:
                        imagejpeg($thumb, $thumb_path, 85);
                        break;
                    case IMAGETYPE_PNG:
                        imagepng($thumb, $thumb_path, 8);
                        break;
                    case IMAGETYPE_GIF:
                        imagegif($thumb, $thumb_path);
                        break;
                }
                
                imagedestroy($thumb);
                imagedestroy($source);
                
                return true;
            }
        }
        
        return false;
    }

    private function deleteImage($filename) {
        $main_image = UPLOAD_PATH . 'gallery/' . $filename;
        $thumb_image = UPLOAD_PATH . 'gallery/thumbs/' . $filename;
        
        if (file_exists($main_image)) {
            unlink($main_image);
        }
        
        if (file_exists($thumb_image)) {
            unlink($thumb_image);
        }
    }

    public function getGalleryStats() {
        $sql = "SELECT 
                    COUNT(*) as total_images,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_images,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_uploads
                FROM {$this->table}";
        
        return $this->queryOne($sql);
    }
}
?>
