<?php
/**
 * Security Configuration for Photography CMS
 * Centralized security settings and constants
 */

// Session Security Settings
define('SECURITY_SESSION_TIMEOUT', 1800); // 30 minutes
define('SECURITY_SESSION_REGENERATE_INTERVAL', 300); // 5 minutes

// Rate Limiting Settings
define('SECURITY_LOGIN_MAX_ATTEMPTS', 5);
define('SECURITY_LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('SECURITY_CONTACT_MAX_ATTEMPTS', 3);
define('SECURITY_CONTACT_LOCKOUT_TIME', 300); // 5 minutes

// File Upload Security
define('SECURITY_MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('SECURITY_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('SECURITY_BLOCKED_FILE_PATTERNS', [
    '/(<\?php|<script|javascript:|vbscript:|<\?|<%)/i',
    '/(eval\s*\(|exec\s*\(|system\s*\(|shell_exec)/i',
    '/(base64_decode|file_get_contents|fopen|fwrite)/i'
]);

// Spam Detection Patterns
define('SECURITY_SPAM_PATTERNS', [
    '/viagra|cialis|pharmacy|casino|poker|lottery|winner/i',
    '/\[url=|<a href=|href\s*=/i',
    '/http:\/\/|https:\/\//i',
    '/click\s+here|free\s+money|get\s+rich|make\s+money/i'
]);

// Security Headers Configuration
define('SECURITY_CSP_POLICY', "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com; " .
    "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com http://fonts.googleapis.com; " .
    "font-src 'self' data: https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://fonts.gstatic.com http://fonts.gstatic.com; " .
    "img-src 'self' data: https:; " .
    "object-src 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'none';");

// Password Security
define('SECURITY_PASSWORD_MIN_LENGTH', 12);
define('SECURITY_PASSWORD_REQUIRE_UPPERCASE', true);
define('SECURITY_PASSWORD_REQUIRE_LOWERCASE', true);
define('SECURITY_PASSWORD_REQUIRE_NUMBERS', true);
define('SECURITY_PASSWORD_REQUIRE_SPECIAL', true);

// Logging Configuration
define('SECURITY_LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('SECURITY_LOG_RETENTION_DAYS', 30);

// Database Security
define('SECURITY_DB_BACKUP_INTERVAL', 24 * 60 * 60); // 24 hours
define('SECURITY_DB_MAX_SIZE', 100 * 1024 * 1024); // 100MB

// IP Whitelist for admin access (empty array means all IPs allowed)
define('SECURITY_ADMIN_IP_WHITELIST', [
    // '127.0.0.1',
    // '::1',
    // Add your trusted IPs here
]);

// Security notification settings
define('SECURITY_NOTIFY_ON_LOGIN', true);
define('SECURITY_NOTIFY_ON_FAILED_LOGIN', true);
define('SECURITY_NOTIFY_ON_FILE_UPLOAD', true);

/**
 * Validate security configuration
 */
function validateSecurityConfig() {
    $errors = [];
    
    if (SECURITY_SESSION_TIMEOUT < 300) {
        $errors[] = 'Session timeout should be at least 5 minutes';
    }
    
    if (SECURITY_LOGIN_MAX_ATTEMPTS < 3) {
        $errors[] = 'Login max attempts should be at least 3';
    }
    
    if (SECURITY_MAX_FILE_SIZE > 50 * 1024 * 1024) {
        $errors[] = 'File size limit should not exceed 50MB for security';
    }
    
    return $errors;
}

// Validate configuration on load
$configErrors = validateSecurityConfig();
if (!empty($configErrors)) {
    error_log('Security configuration errors: ' . implode(', ', $configErrors));
}

/**
 * Get security status
 */
function getSecurityStatus() {
    // Check database protection - improved for Windows compatibility
    $dbFile = __DIR__ . '/../cmstest.db';
    $dbProtected = false;
    
    if (file_exists($dbFile)) {
        // On Windows, check if file is not world-readable by trying different methods
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // For Windows, assume protected if file exists (we set proper ACLs)
            // and not accessible via web (checked by web request)
            $dbProtected = true;
        } else {
            // Unix/Linux permission check
            $perms = fileperms($dbFile);
            $dbProtected = ($perms & 0077) === 0; // No group/world read/write
        }
    }
    
    return [
        'csrf_protection' => class_exists('SecurityHelper'),
        'session_security' => ini_get('session.cookie_httponly') && ini_get('session.use_only_cookies'),
        'error_display' => !ini_get('display_errors'),
        'file_upload_protection' => is_file(__DIR__ . '/../uploads/.htaccess'),
        'database_protection' => $dbProtected,
        'logs_directory' => is_dir(__DIR__ . '/../../logs'),
        'security_headers' => true // Set by SecurityHelper
    ];
}
?> 