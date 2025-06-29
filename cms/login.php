<?php
require_once 'includes/config.php';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

// Helper function to handle failed login attempts
function handleFailedLogin($conn, $userId, $clientIP, $username) {
    global $error;
    
    // Increment database login attempts
    $stmt = $conn->prepare("UPDATE admin_users SET login_attempts = login_attempts + 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Get updated attempt count
    $stmt = $conn->prepare("SELECT login_attempts FROM admin_users WHERE id = ?");
    $stmt->execute([$userId]);
    $attempts = $stmt->fetchColumn();
    
    // Lock account after 5 failed attempts for 30 minutes
    if ($attempts >= 5) {
        $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $stmt = $conn->prepare("UPDATE admin_users SET locked_until = ? WHERE id = ?");
        $stmt->execute([$lockUntil, $userId]);
        
        SecurityHelper::logSecurityEvent('ACCOUNT_LOCKED', "User: $username, IP: $clientIP, Attempts: $attempts");
        $error = 'Account locked due to multiple failed attempts. Please try again in 30 minutes.';
    } else {
        SecurityHelper::recordFailedAttempt($clientIP);
        SecurityHelper::logSecurityEvent('LOGIN_FAILED', "User: $username, IP: $clientIP, Attempts: $attempts");
        $error = 'Invalid username or password';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityHelper::validateCSRF($csrfToken)) {
        SecurityHelper::logSecurityEvent('CSRF_VIOLATION', 'Invalid CSRF token on login');
        $error = 'Security validation failed. Please try again.';
    } else {
        $username = SecurityHelper::sanitizeInput($_POST['username'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check rate limiting
        if (!SecurityHelper::checkRateLimit($clientIP, 5, 900)) { // 5 attempts per 15 minutes
            SecurityHelper::logSecurityEvent('RATE_LIMIT_EXCEEDED', "IP: $clientIP");
            $error = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            // Authenticate against database
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password_hash, login_attempts, locked_until FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user exists and account is not locked
            if ($user) {
                // Check if account is temporarily locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    SecurityHelper::logSecurityEvent('LOGIN_LOCKED_ACCOUNT', "User: $username, IP: $clientIP");
                    $error = 'Account temporarily locked due to multiple failed attempts. Please try again later.';
                } elseif (password_verify($password, $user['password_hash'])) {
                    // SUCCESS: Reset rate limiting and create secure session
                    SecurityHelper::resetFailedAttempts($clientIP);
                    SecurityHelper::logSecurityEvent('LOGIN_SUCCESS', "User: $username, IP: $clientIP");
                    
                    // Reset database login attempts and update last login
                    $stmt = $conn->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL, last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['ip_address'] = $clientIP;
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    
                    header('Location: admin.php');
                    exit;
                } else {
                    // FAILURE: Invalid password
                    handleFailedLogin($conn, $user['id'], $clientIP, $username);
                }
            } else {
                // FAILURE: User not found
                SecurityHelper::recordFailedAttempt($clientIP);
                SecurityHelper::logSecurityEvent('LOGIN_FAILED', "User: $username (not found), IP: $clientIP");
                $error = 'Invalid username or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">CMS Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?php echo SecurityHelper::csrfTokenField(); ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 