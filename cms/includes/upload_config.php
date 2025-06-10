<?php
// Set maximum file upload limits
ini_set('upload_max_filesize', '256M');
ini_set('post_max_size', '512M');
ini_set('max_file_uploads', '100');  // 50 files
ini_set('max_execution_time', '600'); // 10 minutes for larger uploads
ini_set('memory_limit', '1024M'); // Memory limit for larger uploads

// Define upload constants
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB in bytes
define('MAX_FILES', 50); // 50 files
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/ogg', 'audio/wav']);
define('UPLOAD_DIR', realpath(__DIR__.'/../uploads').DIRECTORY_SEPARATOR);
define('UPLOAD_URL',  'uploads/'); // what goes into the DB / <img src>

// Function to validate file uploads
function validateFileUpload($files) {
    $errors = [];
    
    // Check number of files
    if (count($files['name']) > MAX_FILES) {
        $errors[] = "Maximum " . MAX_FILES . " files allowed.";
        return $errors;
    }
    
    // Check each file
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            // Check file size
            if ($files['size'][$key] > MAX_FILE_SIZE) {
                $errors[] = "File '$name' exceeds maximum size of " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
            }
            
            // Check file type
            $type = $files['type'][$key];
            $allowed_types = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_AUDIO_TYPES);
            if (!in_array($type, $allowed_types)) {
                $errors[] = "File '$name' has invalid type. Allowed types: JPG, PNG, GIF, WebP, MP4, WebM, OGG, MP3, WAV";
            }
        } elseif ($files['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Error uploading file '$name': " . getUploadErrorMessage($files['error'][$key]);
        }
    }
    
    return $errors;
}

// Helper function to get upload error messages
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
} 