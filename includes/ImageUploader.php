<?php
/**
 * Smart Image Uploader
 * Hỗ trợ GIF, JPG, PNG, WEBP với auto-resize và optimize
 */

class ImageUploader {
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $maxFileSize = 10485760; // 10MB
    private $maxWidth = 1200;
    private $maxHeight = 1200;
    private $quality = 85;
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?? BASE_PATH . '/assets/images/uploads/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload và xử lý ảnh
     * @param array $file - $_FILES['field_name']
     * @param string $folder - Subfolder (products, logos, etc)
     * @return array - ['success' => bool, 'filename' => string, 'error' => string]
     */
    public function upload($file, $folder = 'products') {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Tạo subfolder
        $targetDir = $this->uploadDir . $folder . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Get file info
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = $this->getMimeType($file['tmp_name']);
        
        // Generate unique filename
        $filename = $this->generateFilename($extension);
        $targetPath = $targetDir . $filename;
        
        // Process image based on type
        try {
            if ($extension === 'gif') {
                // Giữ nguyên GIF (không resize để giữ animation)
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    return ['success' => false, 'error' => 'Không thể upload GIF'];
                }
            } else {
                // Resize và optimize cho JPG, PNG, WEBP
                $result = $this->processImage($file['tmp_name'], $targetPath, $mimeType, $extension);
                if (!$result) {
                    return ['success' => false, 'error' => 'Không thể xử lý ảnh'];
                }
            }
            
            // Return relative path
            $relativePath = $folder . '/' . $filename;
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $relativePath,
                'full_path' => $targetPath,
                'size' => filesize($targetPath),
                'type' => $extension
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['valid' => false, 'error' => 'File không hợp lệ'];
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['valid' => false, 'error' => 'Không có file được upload'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['valid' => false, 'error' => 'File quá lớn'];
            default:
                return ['valid' => false, 'error' => 'Lỗi không xác định'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File không được vượt quá 10MB'];
        }
        
        $mimeType = $this->getMimeType($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'Extension không hợp lệ'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get MIME type safely
     */
    private function getMimeType($filePath) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mimeType;
    }
    
    /**
     * Generate unique filename
     */
    private function generateFilename($extension) {
        return uniqid('img_', true) . '_' . time() . '.' . $extension;
    }
    
    /**
     * Process and optimize image
     */
    private function processImage($sourcePath, $targetPath, $mimeType, $extension) {
        // Load image based on type
        $sourceImage = null;
        
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Get dimensions
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        
        // Calculate new dimensions (maintain aspect ratio)
        list($newWidth, $newHeight) = $this->calculateDimensions($sourceWidth, $sourceHeight);
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Save optimized image
        $result = false;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($newImage, $targetPath, $this->quality);
                break;
            case 'png':
                // PNG quality: 0-9 (0 = no compression, 9 = max compression)
                $pngQuality = floor((100 - $this->quality) / 10);
                $result = imagepng($newImage, $targetPath, $pngQuality);
                break;
            case 'webp':
                $result = imagewebp($newImage, $targetPath, $this->quality);
                break;
        }
        
        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions($width, $height) {
        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return [$width, $height];
        }
        
        $ratio = $width / $height;
        
        if ($width > $height) {
            $newWidth = $this->maxWidth;
            $newHeight = floor($newWidth / $ratio);
        } else {
            $newHeight = $this->maxHeight;
            $newWidth = floor($newHeight * $ratio);
        }
        
        return [$newWidth, $newHeight];
    }
    
    /**
     * Delete image file
     */
    public function delete($relativePath) {
        $fullPath = $this->uploadDir . $relativePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
    
    /**
     * Get file size in human readable format
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
?>
