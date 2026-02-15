<?php
// Include database connection and start session
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

/**
 * Generate Google OAuth URL
 */
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// Pagination settings
$items_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// If logged in, fetch user's saved shots with error handling
$saved_shots = [];
$total_saved_shots = 0;
$db_error = false;

if ($is_logged_in && $user_id) {
    try {
        // Get total count for pagination
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM user_favorites uf
            WHERE uf.user_id = ?
        ");
        
        if (!$count_stmt) {
            throw new Exception("Failed to prepare count statement: " . $conn->error);
        }
        
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_saved_shots = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
        
        // Fetch paginated saved shots
        $stmt = $conn->prepare("
            SELECT s.* 
            FROM shots s 
            JOIN user_favorites uf ON s.id = uf.shot_id 
            WHERE uf.user_id = ? 
            ORDER BY uf.id DESC
            LIMIT ? OFFSET ?
        ");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("iii", $user_id, $items_per_page, $offset);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // For Direct URLs, we always assume they exist (CDN)
                // The client-side onerror will handle broken links if any
                $row['video_exists'] = true;
                $saved_shots[] = $row;
            }
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Log error to file
        error_log("Profile DB Error [User: $user_id]: " . $e->getMessage(), 3, "logs/profile_errors.log");
        $db_error = true;
        $saved_shots = [];
    }
}

// Calculate pagination info
$total_pages = $total_saved_shots > 0 ? ceil($total_saved_shots / $items_per_page) : 0;
$has_more = $page < $total_pages;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <meta name="description" content="View your Zinema.lk profile, manage saved shots, and customize your preferences. Access your favorite content anytime.">
    <meta name="keywords" content="profile, saved shots, favorites, cinedrive, user account, preferences">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Profile - Zinema.lk">
    <meta property="og:description" content="Manage your Zinema.lk profile and saved content">
    <meta property="og:site_name" content="Zinema.lk">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Profile - Zinema.lk">
    <meta name="twitter:description" content="Manage your Zinema.lk profile and saved content">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css?v=2.0">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop-scroll-fix.css">
    <link rel="stylesheet" href="css/desktop-tablet.css">
</head>
<body>
    <?php include '_top_nav.php'; ?>
    
    <div class="app-container">
        <div class="profile-content">
            <?php if (!$is_logged_in): ?>
                <!-- Guest View with Overlay -->
                <div class="guest-profile">
                    <!-- Auth Overlay -->
                    <div class="guest-overlay">
                        <div class="guest-card">
                            <div class="guest-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h2>Profile Access</h2>
                            <p>Sign in to view your profile, access saved content, and manage your preferences</p>
                            
                            <div class="auth-buttons-row">
                                <button onclick="showLoginModal()" class="btn-auth btn-login">
                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                </button>
                                <button onclick="showSignupModal()" class="btn-auth btn-signup">
                                    Create New Account
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Content (Blurred) -->
                    <div class="guest-demo-content">
                        <div class="profile-header">
                            <div class="profile-hero">
                                <div class="profile-avatar">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 50px; height: 50px;">
                                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                        <path d="M6 21C6 17.6863 8.68629 15 12 15C15.3137 15 18 17.6863 18 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="profile-info">
                                    <h1>Guest User</h1>
                                    <div class="profile-stats">
                                        <div class="stat-item">
                                            <span class="stat-number">0</span>
                                            <span class="stat-label">Saved Shots</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">Guest</span>
                                            <span class="stat-label">Status</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <div class="section-header">
                                <h2>My Saved Shots</h2>
                            </div>
                            <div class="no-favorites">
                                <div class="empty-icon">🔒</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Netflix-Style Profile Header -->
                <div class="profile-header">
                    <div class="profile-hero">
                        <div class="profile-avatar">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 50px; height: 50px;">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 21C6 17.6863 8.68629 15 12 15C15.3137 15 18 17.6863 18 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="profile-info">
                            <h1><?php echo htmlspecialchars($username); ?></h1>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-number" id="total-saved-count"><?php echo $total_saved_shots; ?></span>
                                    <span class="stat-label">Saved Shots</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">Member</span>
                                    <span class="stat-label">Status</span>
                                </div>
                            </div>
                            <div class="profile-actions">
                                <button onclick="showLogoutConfirm()" class="btn-profile btn-logout" aria-label="Sign out of your account">
                                    <i class="fas fa-sign-out-alt"></i> Sign Out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Error Alert -->
                <?php if ($db_error): ?>
                    <div class="db-error-alert" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Unable to load saved shots. Please try again later.</span>
                    </div>
                <?php endif; ?>

                <!-- My Saved Shots Section -->
                <div class="profile-section" role="region" aria-label="Saved shots collection">
                    <div class="section-header">
                        <h2>My Saved Shots</h2>
                        <?php if ($total_saved_shots > 0): ?>
                            <span class="pagination-info">Showing <?php echo count($saved_shots); ?> of <?php echo $total_saved_shots; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($saved_shots)): ?>
                        <div class="profile-shots-grid">
                            <?php foreach ($saved_shots as $shot): ?>
                                <div class="profile-shot-item" data-shot-id="<?php echo $shot['id']; ?>">
                                    <?php if ($shot['video_exists']): ?>
                                        <!-- Skeleton Loader -->
                                        <div class="profile-shot-skeleton"></div>
                                        
                                        <!-- Unfavorite Button -->
                                        <button class="btn-unfavorite" data-shot-id="<?php echo $shot['id']; ?>" aria-label="Remove from favorites">
                                            <i class="fas fa-heart-broken"></i>
                                        </button>
                                        
                                        <a href="index.php#shot-<?php echo $shot['id']; ?>" aria-label="View <?php echo htmlspecialchars($shot['title']); ?>">
                                            <video 
                                                src="<?php echo htmlspecialchars($shot['shot_video_file']); ?>"
                                                muted
                                                loop
                                                playsinline
                                                preload="metadata"
                                                onloadeddata="this.closest('.profile-shot-item').classList.add('loaded')"
                                                onerror="this.closest('.profile-shot-item').classList.add('loaded')"
                                                aria-label="Video preview for <?php echo htmlspecialchars($shot['title']); ?>"
                                            ></video>
                                            <div class="profile-shot-overlay">
                                                <p class="profile-shot-title"><?php echo htmlspecialchars($shot['title']); ?></p>
                                            </div>
                                        </a>
                                    <?php else: ?>
                                        <!-- Video Error Placeholder -->
                                        <div class="video-error-placeholder">
                                            <i class="fas fa-video-slash"></i>
                                            <span>Video unavailable</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <?php if ($has_more): ?>
                            <div class="pagination-container">
                                <a href="?page=<?php echo $page + 1; ?>" class="btn-load-more" aria-label="Load more saved shots">
                                    <i class="fas fa-chevron-down"></i> Load More
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-favorites">
                            <div class="empty-icon">⭐</div>
                            <h3>No Saved Shots Yet</h3>
                            <p>Start exploring and save your favorite shots to see them here</p>
                            <a href="index.php">Explore Shots</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeLoginModal()">&times;</button>
            <div class="login-header">
                <h2>🎬 Zinema.lk</h2>
                <p>Welcome back!</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="login-error" class="form-error-message" style="display: none;" role="alert"></div>
            
            <form id="login-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="email" id="login-email" name="email" placeholder="Email" required aria-label="Email address">
                <input type="password" id="login-password" name="password" placeholder="Password" required aria-label="Password">
                <div class="remember-me-container">
                    <input type="checkbox" id="remember-me" name="remember" checked>
                    <label for="remember-me">Remember me</label>
                </div>
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Login</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                
                <!-- Forgot Password Link -->
                <div class="forgot-password-container">
                    <a href="#" onclick="showForgotPasswordModal(); return false;" class="forgot-password-link">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
                
                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>
                
                <!-- Google Sign-In Button -->
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google-signin">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign in with Google
                </a>
                
                <div class="login-footer">
                    <p>No account? <a href="#" onclick="switchToSignup(); return false;" aria-label="Switch to sign up form">Sign Up</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signup-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeSignupModal()">&times;</button>
            <div class="login-header">
                <h2>🚀 Join Us</h2>
                <p>Create Account</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="signup-error" class="form-error-message" style="display: none;" role="alert"></div>
            
            <form id="signup-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="text" id="signup-username" name="username" placeholder="Username" required aria-label="Username">
                <input type="email" id="signup-email" name="email" placeholder="Email" required aria-label="Email address">
                <input type="password" id="signup-password" name="password" placeholder="Password" minlength="6" required aria-label="Password">
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Sign Up</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                
                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>
                
                <!-- Google Sign-In Button -->
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google-signin">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign up with Google
                </a>
                
                <div class="login-footer">
                    <p>Have an account? <a href="#" onclick="switchToLogin(); return false;" aria-label="Switch to login form">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logout-modal" class="logout-confirm-overlay" style="display: none;" role="dialog" aria-labelledby="logout-title" aria-modal="true">
        <div class="logout-confirm-card">
            <h3 id="logout-title">Confirm Sign Out</h3>
            <p>Are you sure you want to sign out of your account?</p>
            <div class="logout-confirm-buttons">
                <button onclick="confirmLogout()" class="btn-confirm-logout" aria-label="Confirm sign out">
                    Yes
                </button>
                <button onclick="closeLogoutConfirm()" class="btn-cancel-logout" aria-label="Cancel sign out">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-password-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeForgotPasswordModal()">&times;</button>
            <div class="login-header">
                <h2>🔑 Reset Password</h2>
                <p>Enter your email to receive a reset link</p>
            </div>
            
            <div id="forgot-error" class="form-error-message" style="display: none;" role="alert"></div>
            <div id="forgot-success" class="form-success-message" style="display: none;"></div>
            
            <form id="forgot-password-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required aria-label="Email address">
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Send Reset Link</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                <div class="login-footer">
                    <p>Remember your password? <a href="#" onclick="switchFromForgotToLogin(); return false;">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Verification Notice Modal -->
    <div id="verification-notice-modal" class="login-overlay" style="display: none;">
        <div class="login-card" style="text-align: center;">
            <button class="close-login-btn" onclick="closeVerificationNotice()">&times;</button>
            <div class="login-header">
                <div style="font-size: 4rem; margin-bottom: 15px;">📧</div>
                <h2 style="color: #51cf66;">Account Created!</h2>
                <p>Please verify your email to continue</p>
            </div>
            
            <div style="background: rgba(81, 207, 102, 0.1); border: 1px solid rgba(81, 207, 102, 0.3); border-radius: 8px; padding: 20px; margin: 20px 0;">
                <p style="color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 10px;">
                    We've sent a verification link to:
                </p>
                <p id="verification-email-display" style="color: #ff3366; font-weight: bold; font-size: 1.1rem; word-break: break-all;"></p>
            </div>
            
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 20px;">
                Click the link in your email to activate your account
            </p>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Primary: Open Email -->
                <a id="open-email-btn" href="https://mail.google.com" target="_blank" class="btn-login-submit" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-envelope-open"></i> Open Email Inbox
                </a>
                
                <!-- Secondary: Go to Login -->
                <button onclick="closeVerificationNotice(); showLoginModal();" style="background: transparent; border: 1px solid rgba(138, 43, 226, 0.5); color: #8a2be2; padding: 12px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: all 0.3s;">
                    Go to Login
                </button>
                
                <!-- Tertiary: Resend -->
                <button id="resend-verification-btn" onclick="resendVerification();" style="background: transparent; border: none; color: rgba(255,255,255,0.5); padding: 10px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s;">
                    <span class="btn-text"><i class="fas fa-redo"></i> Resend Verification Email</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </div>
            
            <p id="resend-message" style="margin-top: 10px; font-size: 0.9rem; display: none;"></p>
        </div>
    </div>

    <script>
        // ========================================
        // Modal Management
        // ========================================
        
        // Verification Notice Modal
        let pendingVerificationEmail = '';
        
        function showVerificationNotice(email) {
            pendingVerificationEmail = email;
            document.getElementById('verification-email-display').textContent = email;
            document.getElementById('resend-message').style.display = 'none';
            
            // Set email inbox link based on domain
            const emailDomain = email.split('@')[1]?.toLowerCase();
            const openEmailBtn = document.getElementById('open-email-btn');
            
            const emailLinks = {
                'gmail.com': 'https://mail.google.com',
                'yahoo.com': 'https://mail.yahoo.com',
                'outlook.com': 'https://outlook.live.com',
                'hotmail.com': 'https://outlook.live.com',
                'live.com': 'https://outlook.live.com',
                'icloud.com': 'https://www.icloud.com/mail',
                'protonmail.com': 'https://mail.protonmail.com',
                'zoho.com': 'https://mail.zoho.com'
            };
            
            openEmailBtn.href = emailLinks[emailDomain] || 'https://mail.google.com';
            
            const modal = document.getElementById('verification-notice-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
        }
        
        function closeVerificationNotice() {
            document.getElementById('verification-notice-modal').style.display = 'none';
        }
        
        async function resendVerification() {
            const btn = document.getElementById('resend-verification-btn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoader = btn.querySelector('.btn-loader');
            const msgEl = document.getElementById('resend-message');
            
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-block';
            msgEl.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('email', pendingVerificationEmail);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                
                const response = await fetch('ajax_resend_verification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                
                msgEl.style.display = 'block';
                if (data.success) {
                    msgEl.style.color = '#51cf66';
                    msgEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                } else {
                    msgEl.style.color = '#ff6b6b';
                    msgEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                }
            } catch (error) {
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                msgEl.style.display = 'block';
                msgEl.style.color = '#ff6b6b';
                msgEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
            }
        }
        
        // Login Modal Functions
        function showLoginModal() {
            const modal = document.getElementById('login-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeLoginModal() {
            document.getElementById('login-modal').style.display = 'none';
            // Clear error message
            hideError('login-error');
            // Reset form
            document.getElementById('login-form').reset();
        }

        // ========================================
        // AJAX Form Submission
        // ========================================
        
        // Login Form AJAX Handler
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    
                    // Hide previous errors
                    hideError('login-error');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch('ajax_login.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        if (data.success) {
                            // Success - redirect to profile
                            window.location.href = data.redirect || 'profile.php';
                        } else {
                            // Show error message
                            showError('login-error', data.message);
                        }
                    } catch (error) {
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        // Show error
                        showError('login-error', 'An error occurred. Please try again.');
                        console.error('Login error:', error);
                    }
                });
            }
            
            // Signup Form AJAX Handler
            const signupForm = document.getElementById('signup-form');
            if (signupForm) {
                signupForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    
                    // Hide previous errors
                    hideError('signup-error');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch('ajax_signup.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        if (data.success) {
                            if (data.requires_verification) {
                                // Show verification required message
                                closeSignupModal();
                                showVerificationNotice(formData.get('email'));
                            } else {
                                // Success - redirect to profile
                                window.location.href = data.redirect || 'profile.php';
                            }
                        } else {
                            // Show error message
                            showError('signup-error', data.message);
                        }
                    } catch (error) {
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        // Show error
                        showError('signup-error', 'An error occurred. Please try again.');
                        console.error('Signup error:', error);
                    }
                });
            }
        });
        
        // Helper function to show error messages
        function showError(elementId, message) {
            const errorDiv = document.getElementById(elementId);
            if (errorDiv) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                errorDiv.style.display = 'block';
                
                // Scroll error into view
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        // Helper function to hide error messages
        function hideError(elementId) {
            const errorDiv = document.getElementById(elementId);
            if (errorDiv) {
                errorDiv.style.display = 'none';
                errorDiv.innerHTML = '';
            }
        }

        // ========================================
        // Forgot Password Modal Functions
        // ========================================
        function showForgotPasswordModal() {
            closeLoginModal();
            const modal = document.getElementById('forgot-password-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
            // Pre-fill email from login form if available
            const loginEmail = document.getElementById('login-email');
            const forgotEmail = document.getElementById('forgot-email');
            if (loginEmail && forgotEmail && loginEmail.value) {
                forgotEmail.value = loginEmail.value;
            }
            setTimeout(() => {
                document.getElementById('forgot-email').focus();
            }, 100);
        }
        
        function closeForgotPasswordModal() {
            document.getElementById('forgot-password-modal').style.display = 'none';
            hideError('forgot-error');
            document.getElementById('forgot-success').style.display = 'none';
            document.getElementById('forgot-password-form').reset();
        }
        
        function switchFromForgotToLogin() {
            closeForgotPasswordModal();
            showLoginModal();
        }
        
        // Forgot Password Form AJAX Handler
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('forgot-password-form');
            if (forgotForm) {
                forgotForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    const successDiv = document.getElementById('forgot-success');
                    
                    hideError('forgot-error');
                    successDiv.style.display = 'none';
                    
                    // Show loading
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';
                    
                    try {
                        const formData = new FormData(this);
                        
                        const response = await fetch('ajax_forgot_password.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        if (data.success) {
                            successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                            successDiv.style.display = 'block';
                            this.reset();
                        } else {
                            showError('forgot-error', data.message);
                        }
                    } catch (error) {
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        showError('forgot-error', 'An error occurred. Please try again.');
                        console.error('Forgot password error:', error);
                    }
                });
            }
        });

        // Signup Modal Functions
        function showSignupModal() {
            const modal = document.getElementById('signup-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeSignupModal() {
            document.getElementById('signup-modal').style.display = 'none';
            // Clear error message
            hideError('signup-error');
            // Reset form
            document.getElementById('signup-form').reset();
        }

        // Switch between modals
        function switchToSignup() {
            closeLoginModal();
            showSignupModal();
        }

        function switchToLogin() {
            closeSignupModal();
            showLoginModal();
        }

        // Logout Confirmation Functions
        function showLogoutConfirm() {
            const modal = document.getElementById('logout-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
            // Focus confirm button
            setTimeout(() => {
                const confirmBtn = modal.querySelector('.btn-confirm-logout');
                if (confirmBtn) confirmBtn.focus();
            }, 100);
        }

        function closeLogoutConfirm() {
            document.getElementById('logout-modal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // ========================================
        // Focus Trap for Accessibility
        // ========================================
        function trapFocus(modal) {
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            modal.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        }

        // ========================================
        // Event Listeners
        // ========================================
        
        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const loginModal = document.getElementById('login-modal');
            const signupModal = document.getElementById('signup-modal');
            const logoutModal = document.getElementById('logout-modal');
            const forgotModal = document.getElementById('forgot-password-modal');
            
            if (e.target === loginModal) {
                closeLoginModal();
            }
            if (e.target === signupModal) {
                closeSignupModal();
            }
            if (e.target === logoutModal) {
                closeLogoutConfirm();
            }
            if (e.target === forgotModal) {
                closeForgotPasswordModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginModal();
                closeSignupModal();
                closeLogoutConfirm();
                closeForgotPasswordModal();
                closeVerificationNotice();
            }
        });


        // ========================================
        // Lazy Loading & Performance
        // ========================================
        
        // Single DOMContentLoaded handler for all initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
            
            // Initialize autoplay preference (default: enabled)
            if (localStorage.getItem('autoplay') === null) {
                localStorage.setItem('autoplay', 'true');
            }
            
            // ========================================
            // Client-Side Form Validation
            // ========================================
            
            // Email validation regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Login email validation
            const loginEmail = document.getElementById('login-email');
            if (loginEmail) {
                loginEmail.addEventListener('blur', function() {
                    validateEmailField(this);
                });
                loginEmail.addEventListener('input', function() {
                    if (this.classList.contains('invalid') || this.classList.contains('valid')) {
                        validateEmailField(this);
                    }
                });
            }
            
            // Signup form validation
            const signupEmail = document.getElementById('signup-email');
            const signupUsername = document.getElementById('signup-username');
            const signupPassword = document.getElementById('signup-password');
            
            if (signupEmail) {
                signupEmail.addEventListener('blur', function() {
                    validateEmailField(this);
                });
                signupEmail.addEventListener('input', function() {
                    if (this.classList.contains('invalid') || this.classList.contains('valid')) {
                        validateEmailField(this);
                    }
                });
            }
            
            if (signupUsername) {
                signupUsername.addEventListener('blur', function() {
                    validateUsernameField(this);
                });
                signupUsername.addEventListener('input', function() {
                    if (this.classList.contains('invalid') || this.classList.contains('valid')) {
                        validateUsernameField(this);
                    }
                });
            }
            
            if (signupPassword) {
                signupPassword.addEventListener('input', function() {
                    validatePasswordField(this);
                });
            }
            
            // Validation helper functions
            function validateEmailField(input) {
                const value = input.value.trim();
                if (value.length === 0) {
                    input.classList.remove('valid', 'invalid');
                    return true;
                }
                if (emailRegex.test(value)) {
                    input.classList.remove('invalid');
                    input.classList.add('valid');
                    return true;
                } else {
                    input.classList.remove('valid');
                    input.classList.add('invalid');
                    return false;
                }
            }
            
            function validateUsernameField(input) {
                const value = input.value.trim();
                const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
                if (value.length === 0) {
                    input.classList.remove('valid', 'invalid');
                    return true;
                }
                if (usernameRegex.test(value)) {
                    input.classList.remove('invalid');
                    input.classList.add('valid');
                    return true;
                } else {
                    input.classList.remove('valid');
                    input.classList.add('invalid');
                    return false;
                }
            }
            
            function validatePasswordField(input) {
                const value = input.value;
                if (value.length === 0) {
                    input.classList.remove('valid', 'invalid');
                    return true;
                }
                if (value.length >= 6) {
                    input.classList.remove('invalid');
                    input.classList.add('valid');
                    return true;
                } else {
                    input.classList.remove('valid');
                    input.classList.add('invalid');
                    return false;
                }
            }
            
            // ========================================
            // Fallback: Add loaded class after timeout for any videos that didn't fire loadeddata
            // ========================================
            setTimeout(() => {
                document.querySelectorAll('.profile-shot-item:not(.loaded)').forEach(item => {
                    item.classList.add('loaded');
                });
            }, 3000);
            
            // ========================================
            // Event Delegation for Video Hover-to-Play
            // ========================================
            const shotsGrid = document.querySelector('.profile-shots-grid');
            if (shotsGrid) {
                shotsGrid.addEventListener('mouseenter', function(e) {
                    const shotItem = e.target.closest('.profile-shot-item');
                    if (shotItem) {
                        const video = shotItem.querySelector('video');
                        if (video && video.src && localStorage.getItem('autoplay') !== 'false') {
                            video.play().catch(() => {}); // Ignore autoplay errors
                        }
                    }
                }, true);
                
                shotsGrid.addEventListener('mouseleave', function(e) {
                    const shotItem = e.target.closest('.profile-shot-item');
                    if (shotItem) {
                        const video = shotItem.querySelector('video');
                        if (video) {
                            video.pause();
                            video.currentTime = 0;
                        }
                    }
                }, true);
            }
            
            // ========================================
            // Unfavorite Button Handler
            // ========================================
            document.querySelectorAll('.btn-unfavorite').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const shotId = this.getAttribute('data-shot-id');
                    const shotItem = this.closest('.profile-shot-item');
                    
                    // Add loading state
                    this.classList.add('loading');
                    this.disabled = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('shot_id', shotId);
                        
                        const response = await fetch('api/favorite_shot.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.status === 'success' && data.action === 'unfavorited') {
                            // Animate removal
                            shotItem.style.transform = 'scale(0.8)';
                            shotItem.style.opacity = '0';
                            
                            setTimeout(() => {
                                shotItem.remove();
                                
                                // Update count
                                const countEl = document.getElementById('total-saved-count');
                                if (countEl) {
                                    const currentCount = parseInt(countEl.textContent) || 0;
                                    countEl.textContent = Math.max(0, currentCount - 1);
                                }
                                
                                // If no more shots, show empty state
                                const grid = document.querySelector('.profile-shots-grid');
                                if (grid && grid.children.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            alert(data.message || 'Failed to remove from favorites');
                            this.classList.remove('loading');
                            this.disabled = false;
                        }
                    } catch (error) {
                        console.error('Unfavorite error:', error);
                        alert('An error occurred. Please try again.');
                        this.classList.remove('loading');
                        this.disabled = false;
                    }
                });
            });
            
            console.log('Profile page loaded successfully');
        });
        
        // Toggle autoplay preference (can be called from UI if needed)
        function toggleAutoplay() {
            const current = localStorage.getItem('autoplay') !== 'false';
            localStorage.setItem('autoplay', current ? 'false' : 'true');
            console.log('Autoplay ' + (current ? 'disabled' : 'enabled'));
        }
    </script>
</body>
</html>