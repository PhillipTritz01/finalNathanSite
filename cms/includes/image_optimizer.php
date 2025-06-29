<?php
/**
 * Image Optimizer for Photography CMS
 * Handles compression, resizing, and WebP conversion
 */

class ImageOptimizer {
    
    public static function optimizeImage($sourcePath, $quality = 85, $maxWidth = 1920, $maxHeight = 1080) {
        try {
            // Check if GD extension is available
            if (!extension_loaded('gd')) {
                error_log('ImageOptimizer: GD extension not available');
                return false;
            }
            
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Calculate new dimensions while maintaining aspect ratio
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            if ($ratio >= 1) {
                $ratio = 1; // Don't upscale
            }
            
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            
            // Create image resource based on type
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                case 'image/webp':
                    $sourceImage = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return false;
            }
            
            if (!$sourceImage) {
                return false;
            }
            
            // Create new image
            $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG and GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($optimizedImage, false);
                imagesavealpha($optimizedImage, true);
                $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
                imagefill($optimizedImage, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled(
                $optimizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Generate WebP version for modern browsers
            $webpPath = self::getWebPPath($sourcePath);
            if (function_exists('imagewebp')) {
                imagewebp($optimizedImage, $webpPath, $quality);
            }
            
            // Save optimized original format
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($optimizedImage, $sourcePath, $quality);
                    break;
                case 'image/png':
                    imagepng($optimizedImage, $sourcePath, (int)(9 - ($quality / 100) * 9));
                    break;
                case 'image/gif':
                    imagegif($optimizedImage, $sourcePath);
                    break;
                case 'image/webp':
                    imagewebp($optimizedImage, $sourcePath, $quality);
                    break;
            }
            
            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($optimizedImage);
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function generateThumbnail($sourcePath, $width = 300, $height = 300, $quality = 80) {
        try {
            // Check if GD extension is available
            if (!extension_loaded('gd')) {
                error_log('ImageOptimizer: GD extension not available for thumbnail generation');
                return false;
            }
            
            $thumbnailPath = self::getThumbnailPath($sourcePath);
            
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Create source image
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                case 'image/webp':
                    $sourceImage = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return false;
            }
            
            if (!$sourceImage) {
                return false;
            }
            
            // Calculate crop dimensions (center crop)
            $ratio = max($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            
            $cropX = (int)(($newWidth - $width) / 2);
            $cropY = (int)(($newHeight - $height) / 2);
            
            // Create thumbnail
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            $thumbnail = imagecreatetruecolor($width, $height);
            
            // Resize and crop
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            imagecopy(
                $thumbnail, $resizedImage,
                0, 0, $cropX, $cropY,
                $width, $height
            );
            
            // Save thumbnail as JPEG for smaller file size
            imagejpeg($thumbnail, $thumbnailPath, $quality);
            
            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            imagedestroy($thumbnail);
            
            return $thumbnailPath;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function getWebPPath($imagePath) {
        $pathInfo = pathinfo($imagePath);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    }
    
    public static function getThumbnailPath($imagePath) {
        $pathInfo = pathinfo($imagePath);
        return $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
    }
    
    public static function hasWebPVersion($imagePath) {
        return file_exists(self::getWebPPath($imagePath));
    }
    
    public static function hasThumbnail($imagePath) {
        return file_exists(self::getThumbnailPath($imagePath));
    }
}
?> 