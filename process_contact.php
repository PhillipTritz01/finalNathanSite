<?php
require_once 'cms/includes/config.php';

// Security headers
SecurityHelper::setSecurityHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    SecurityHelper::logSecurityEvent('INVALID_CONTACT_METHOD', 'Non-POST request to contact form');
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method not allowed');
}

// Rate limiting for contact form (prevent spam)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!SecurityHelper::checkRateLimit('contact_' . $clientIP, 3, 300)) { // 3 attempts per 5 minutes
    SecurityHelper::logSecurityEvent('CONTACT_RATE_LIMIT', "IP: $clientIP");
    header('Location: contact.php?error=rate_limit');
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!SecurityHelper::validateCSRF($csrfToken)) {
    SecurityHelper::logSecurityEvent('CONTACT_CSRF_VIOLATION', "IP: $clientIP");
    SecurityHelper::recordFailedAttempt('contact_' . $clientIP);
    header('Location: contact.php?error=security');
    exit;
}

// Sanitize and validate input
$name = SecurityHelper::sanitizeInput($_POST['name'] ?? '', 'string');
$email = SecurityHelper::sanitizeInput($_POST['email'] ?? '', 'email');
$subject = SecurityHelper::sanitizeInput($_POST['subject'] ?? '', 'string');
$message = SecurityHelper::sanitizeInput($_POST['message'] ?? '', 'string');

$errors = [];

// Validate required fields
if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required (minimum 2 characters)';
}

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($subject) || strlen($subject) < 5) {
    $errors[] = 'Subject is required (minimum 5 characters)';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Message is required (minimum 10 characters)';
}

// Check for spam patterns
$spamPatterns = [
    '/viagra|cialis|pharmacy|casino|poker/i',
    '/\[url=|<a href=/i',
    '/http:\/\/|https:\/\//i'
];

foreach ($spamPatterns as $pattern) {
    if (preg_match($pattern, $message) || preg_match($pattern, $subject)) {
        SecurityHelper::logSecurityEvent('CONTACT_SPAM_DETECTED', "IP: $clientIP, Pattern: $pattern");
        SecurityHelper::recordFailedAttempt('contact_' . $clientIP);
        header('Location: contact.php?error=spam');
        exit;
    }
}

// Check message length limits
if (strlen($message) > 5000) {
    $errors[] = 'Message is too long (maximum 5000 characters)';
}

if (!empty($errors)) {
    SecurityHelper::logSecurityEvent('CONTACT_VALIDATION_FAILED', implode(', ', $errors));
    SecurityHelper::recordFailedAttempt('contact_' . $clientIP);
    header('Location: contact.php?error=validation&details=' . urlencode(implode(', ', $errors)));
    exit;
}

// Log successful contact form submission
SecurityHelper::logSecurityEvent('CONTACT_FORM_SUBMITTED', "Name: $name, Email: $email, IP: $clientIP");

// Here you would typically send an email
// For now, we'll just log it and redirect to success
try {
    // Example: Store in database for admin review
    $conn = getDBConnection();
    $conn->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        ip_address TEXT,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message, $clientIP]);
    
    // Reset rate limiting on successful submission
    SecurityHelper::resetFailedAttempts('contact_' . $clientIP);
    
    header('Location: contact.php?success=1');
    exit;
    
} catch (Exception $e) {
    SecurityHelper::logSecurityEvent('CONTACT_SAVE_ERROR', $e->getMessage());
    header('Location: contact.php?error=system');
    exit;
}
?> 