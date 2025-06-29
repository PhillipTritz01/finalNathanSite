<?php
require_once 'includes/config.php';

// Enhanced Session Security
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to admin settings without login');
    header('Location: login.php'); 
    exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    SecurityHelper::logSecurityEvent('SESSION_TIMEOUT', 'Session expired in admin settings');
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

// Validate session integrity
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
    SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "Admin settings access with IP mismatch");
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

$_SESSION['last_activity'] = time();

// Handle logout
if (isset($_GET['logout'])) { 
    SecurityHelper::logSecurityEvent('LOGOUT', 'Admin logout from settings');
    session_unset(); session_destroy(); header('Location: login.php'); exit; 
}

$conn = getDBConnection();
$userId = $_SESSION['admin_user_id'];
$currentUsername = $_SESSION['admin_username'];

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityHelper::validateCSRF($csrfToken)) {
        SecurityHelper::logSecurityEvent('CSRF_VIOLATION', 'Invalid CSRF token in admin settings');
        $error = 'Security validation failed. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'change_username') {
            $newUsername = SecurityHelper::sanitizeInput($_POST['new_username'] ?? '', 'string');
            $currentPassword = $_POST['current_password'] ?? '';
            
            // Validate inputs
            if (empty($newUsername) || strlen($newUsername) < 3) {
                $error = 'Username must be at least 3 characters long.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newUsername)) {
                $error = 'Username can only contain letters, numbers, underscores, and hyphens.';
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password_hash'])) {
                    SecurityHelper::logSecurityEvent('SETTINGS_INVALID_PASSWORD', "User: $currentUsername, Action: change_username");
                    $error = 'Current password is incorrect.';
                } else {
                    // Check if username already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?");
                    $stmt->execute([$newUsername, $userId]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Username already exists. Please choose a different one.';
                    } else {
                        // Update username
                        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$newUsername, $userId]);
                        
                        $_SESSION['admin_username'] = $newUsername;
                        SecurityHelper::logSecurityEvent('USERNAME_CHANGED', "From: $currentUsername, To: $newUsername");
                        $success = 'Username changed successfully!';
                        $currentUsername = $newUsername;
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate password
            if (empty($currentPassword)) {
                $error = 'Current password is required.';
            } elseif (empty($newPassword) || strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } else {
                // Check password strength
                $strengthErrors = [];
                if (!preg_match('/[A-Z]/', $newPassword)) {
                    $strengthErrors[] = 'at least one uppercase letter';
                }
                if (!preg_match('/[a-z]/', $newPassword)) {
                    $strengthErrors[] = 'at least one lowercase letter';
                }
                if (!preg_match('/[0-9]/', $newPassword)) {
                    $strengthErrors[] = 'at least one number';
                }
                if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                    $strengthErrors[] = 'at least one special character';
                }
                
                if (!empty($strengthErrors)) {
                    $error = 'Password must contain: ' . implode(', ', $strengthErrors) . '.';
                } else {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    if (!password_verify($currentPassword, $user['password_hash'])) {
                        SecurityHelper::logSecurityEvent('SETTINGS_INVALID_PASSWORD', "User: $currentUsername, Action: change_password");
                        $error = 'Current password is incorrect.';
                    } else {
                        // Hash new password and update
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$hashedPassword, $userId]);
                        
                        SecurityHelper::logSecurityEvent('PASSWORD_CHANGED', "User: $currentUsername");
                        $success = 'Password changed successfully!';
                    }
                }
            }
        } elseif ($action === 'change_email') {
            $newEmail = SecurityHelper::sanitizeInput($_POST['new_email'] ?? '', 'email');
            $currentPassword = $_POST['current_password'] ?? '';
            
            if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password_hash'])) {
                    SecurityHelper::logSecurityEvent('SETTINGS_INVALID_PASSWORD', "User: $currentUsername, Action: change_email");
                    $error = 'Current password is incorrect.';
                } else {
                    // Update email
                    $stmt = $conn->prepare("UPDATE admin_users SET email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$newEmail, $userId]);
                    
                    SecurityHelper::logSecurityEvent('EMAIL_CHANGED', "User: $currentUsername, New Email: $newEmail");
                    $success = 'Email address updated successfully!';
                }
            }
        }
    }
}

// Get current user info
$stmt = $conn->prepare("SELECT username, email, created_at, last_login FROM admin_users WHERE id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Photography CMS</title>
    
    <!-- Apply theme immediately to prevent flash -->
    <script>
    (function() {
        const saved = localStorage.getItem('theme');
        if (saved) {
            document.documentElement.classList.add(saved + '-mode');
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.add('light-mode');
        }
    })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
    :root {
        --bg: #f8f9fa;
        --text: #222;
        --sidebar-bg: #343a40;
        --sidebar-text: #fff;
        --card-bg: #fff;
        --border: #dee2e6;
        --btn-bg: #0d6efd;
        --btn-text: #fff;
        --btn-border: #0a58ca;
        --btn-outline-bg: transparent;
        --btn-outline-text: #6c757d;
        --btn-outline-border: #6c757d;
    }
    body.dark-mode, html.dark-mode {
        --bg: #181a1b;
        --text: #e0e0e0;
        --sidebar-bg: #23272b;
        --sidebar-text: #fff;
        --card-bg: #23272b;
        --border: #444;
        --btn-bg: #2563eb;
        --btn-text: #fff;
        --btn-border: #1a4fb4;
        --btn-outline-bg: transparent;
        --btn-outline-text: #adb5bd;
        --btn-outline-border: #6c757d;
    }
    
    body { 
        background: var(--bg) !important; 
        color: var(--text) !important;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1), color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .sidebar { 
        background: var(--sidebar-bg) !important; 
        color: var(--sidebar-text) !important;
        min-height: 100vh;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .card {
        background: var(--card-bg) !important;
        border-color: var(--border) !important;
        color: var(--text) !important;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1), border-color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .btn-primary {
        background-color: var(--btn-bg) !important;
        color: var(--btn-text) !important;
        border-color: var(--btn-border) !important;
    }
    .btn-primary:hover, .btn-primary:focus {
        background-color: var(--btn-border) !important;
        color: var(--btn-text) !important;
    }
    .btn-outline-secondary {
        background: var(--btn-outline-bg) !important;
        color: var(--btn-outline-text) !important;
        border-color: var(--btn-outline-border) !important;
    }
    .btn-outline-secondary:hover, .btn-outline-secondary:focus {
        background-color: var(--btn-outline-border) !important;
        color: var(--card-bg) !important;
    }
    .text-muted {
        color: var(--btn-outline-text) !important;
    }
    
    /* Theme Switch Styles */
    .theme-switch-wrap {
        position: absolute;
        top: 18px;
        right: 200px;
        z-index: 3000;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        user-select: none;
    }
    .theme-switch-label {
        font-size: 1rem;
        color: var(--text);
        opacity: 0.7;
        letter-spacing: 0.05em;
        transition: color 0.3s, opacity 0.3s;
    }
    .theme-switch {
        width: 56px;
        height: 28px;
        background: #e0e0e0;
        border-radius: 14px;
        position: relative;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
    }
    body.dark-mode .theme-switch, html.dark-mode .theme-switch {
        background: #444;
    }
    .theme-switch-knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0,0,0,0.12);
        transition: left 0.3s cubic-bezier(.4,0,.2,1), background 0.3s;
    }
    body.dark-mode .theme-switch-knob, html.dark-mode .theme-switch-knob {
        left: 31px;
        background: #e0e0e0;
    }
    .theme-switch-knob::before {
        content: '';
        display: block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #bbb;
        position: absolute;
        top: 6px;
        left: 6px;
        opacity: 0.5;
    }
    body.dark-mode .theme-switch-knob::before, html.dark-mode .theme-switch-knob::before {
        background: #222;
        opacity: 0.5;
    }
    .theme-switch:active .theme-switch-knob {
        box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    }
    
    .password-strength {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .strength-weak { color: #dc3545; }
    .strength-medium { color: #ffc107; }
    .strength-strong { color: #28a745; }
    .form-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1), border-color 0.4s cubic-bezier(.4,0,.2,1);
    }
    </style>
</head>
<body>
    <div class="theme-switch-wrap">
        <span class="theme-switch-label">DARK</span>
        <div class="theme-switch" id="themeToggle">
            <div class="theme-switch-knob"></div>
        </div>
        <span class="theme-switch-label">LIGHT</span>
    </div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4 text-white">CMS</h3>
                <nav class="nav flex-column">
                    <span class="nav-link text-white fw-bold">Portfolio Management</span>
                    <a class="nav-link text-white" href="admin.php">Manage Portfolio</a>
                    <a class="nav-link text-white" href="portfolio-overview.php">Portfolio Overview</a>
                    <a class="nav-link text-white" href="gallery-settings.php">Gallery Settings</a>
                    <span class="nav-link text-white fw-bold mt-3">Page Management</span>
                    <a class="nav-link text-white" href="page-manager.php">Page Content</a>
                    <a class="nav-link text-white" href="slideshow-manager.php">Hero Slideshow</a>
                    <span class="nav-link text-white fw-bold mt-3">System</span>
                    <a class="nav-link text-white" href="security-status.php">Security Status</a>
                    <span class="nav-link text-white fw-bold">Admin Settings</span>
                    <a class="nav-link text-white" href="performance-monitor.php">Performance Monitor</a>
                    <a class="nav-link text-white" href="maintenance.php">Maintenance</a>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-gear"></i> Admin Settings</h1>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> View Website
                    </a>
                </div>
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-cog me-2"></i>Admin Settings
            </h1>
        </div>
    </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

    <div class="row">
        <!-- Account Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Username:</strong> <?= htmlspecialchars($userInfo['username']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($userInfo['email'] ?? 'Not set') ?></p>
                    <p><strong>Account Created:</strong> <?= date('M j, Y', strtotime($userInfo['created_at'])) ?></p>
                    <p><strong>Last Login:</strong> <?= $userInfo['last_login'] ? date('M j, Y H:i', strtotime($userInfo['last_login'])) : 'Never' ?></p>
                </div>
            </div>
        </div>

        <!-- Settings Forms -->
        <div class="col-md-8">
            <!-- Change Username -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-person-gear me-2"></i>Change Username</h5>
                <form method="POST">
                    <?= SecurityHelper::csrfTokenField() ?>
                    <input type="hidden" name="action" value="change_username">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="new_username" class="form-label">New Username</label>
                            <input type="text" class="form-control" id="new_username" name="new_username" 
                                   pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, underscores, and hyphens allowed"
                                   minlength="3" required>
                            <div class="form-text">3+ characters, letters, numbers, _ and - only</div>
                        </div>
                        <div class="col-md-6">
                            <label for="current_password_1" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password_1" name="current_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-save me-2"></i>Update Username
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-key me-2"></i>Change Password</h5>
                <form method="POST">
                    <?= SecurityHelper::csrfTokenField() ?>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label for="current_password_2" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password_2" name="current_password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="8" required>
                            <div id="password-strength" class="password-strength"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="8" required>
                            <div id="password-match" class="form-text"></div>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted">
                            Password must contain: uppercase letter, lowercase letter, number, and special character (8+ characters)
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-3" id="change-password-btn" disabled>
                        <i class="bi bi-save me-2"></i>Update Password
                    </button>
                </form>
            </div>

            <!-- Change Email -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-envelope me-2"></i>Change Email Address</h5>
                <form method="POST">
                    <?= SecurityHelper::csrfTokenField() ?>
                    <input type="hidden" name="action" value="change_email">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="new_email" class="form-label">New Email Address</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" 
                                   value="<?= htmlspecialchars($userInfo['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="current_password_3" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password_3" name="current_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-save me-2"></i>Update Email
                    </button>
                </form>
            </div>
        </div>
    </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Theme toggle logic
    function setTheme(mode) {
        document.documentElement.classList.remove('light-mode', 'dark-mode');
        document.body.classList.remove('light-mode', 'dark-mode');
        document.documentElement.classList.add(mode + '-mode');
        document.body.classList.add(mode + '-mode');
        localStorage.setItem('theme', mode);
    }
    
    function toggleTheme() {
        const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
        setTheme(isDark ? 'light' : 'dark');
    }
    
    // Initialize theme toggle
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('themeToggle').onclick = toggleTheme;
        
        // Apply saved theme or system preference
        const saved = localStorage.getItem('theme');
        if (saved) {
            setTheme(saved);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
    });
    
    <script>
// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    const changeBtn = document.getElementById('change-password-btn');
    
    let score = 0;
    let feedback = [];
    
    if (password.length >= 8) score++;
    else feedback.push('8+ characters');
    
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('uppercase letter');
    
    if (/[a-z]/.test(password)) score++;
    else feedback.push('lowercase letter');
    
    if (/[0-9]/.test(password)) score++;
    else feedback.push('number');
    
    if (/[^A-Za-z0-9]/.test(password)) score++;
    else feedback.push('special character');
    
    if (score < 3) {
        strengthDiv.innerHTML = '<span class="strength-weak">Weak - Missing: ' + feedback.join(', ') + '</span>';
        strengthDiv.className = 'password-strength strength-weak';
    } else if (score < 5) {
        strengthDiv.innerHTML = '<span class="strength-medium">Medium - Missing: ' + feedback.join(', ') + '</span>';
        strengthDiv.className = 'password-strength strength-medium';
    } else {
        strengthDiv.innerHTML = '<span class="strength-strong">Strong password!</span>';
        strengthDiv.className = 'password-strength strength-strong';
    }
    
    checkPasswordMatch();
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    const changeBtn = document.getElementById('change-password-btn');
    
    if (confirmPassword === '') {
        matchDiv.textContent = '';
        changeBtn.disabled = true;
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<span class="text-success">Passwords match!</span>';
        
        // Enable button only if password is strong enough
        const password = newPassword;
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        changeBtn.disabled = score < 5;
    } else {
        matchDiv.innerHTML = '<span class="text-danger">Passwords do not match</span>';
        changeBtn.disabled = true;
    }
}
</script>

</body>
</html> 