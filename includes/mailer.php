<?php
/**
 * PHPMailer Wrapper for Zinema.lk
 * Uses Gmail SMTP for sending emails
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files (using local copy, no Composer needed)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Send an email using PHPMailer with Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML email body
 * @param string $textBody Plain text fallback (optional)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        
        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}

/**
 * Send password reset email
 * 
 * @param string $email User's email
 * @param string $username User's username
 * @param string $resetLink Full reset URL with token
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($email, $username, $resetLink) {
    $subject = "Reset Your Password - Zinema.lk";
    $year = date('Y');
    
    // Use inline styles for Gmail compatibility (Gmail strips <style> tags)
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="500" cellspacing="0" cellpadding="0" style="background-color: #1a1a2e; border-radius: 12px; overflow: hidden; max-width: 500px;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #8a2be2, #ff3366); padding: 25px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">🎬 Zinema.lk</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px; background-color: #1a1a2e;">
                            <h2 style="color: #ff3366; margin: 0 0 20px 0; font-size: 22px;">Password Reset Request</h2>
                            
                            <p style="color: #ffffff; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                                Hi <strong style="color: #ff3366;">{$username}</strong>,
                            </p>
                            
                            <p style="color: #cccccc; font-size: 15px; line-height: 1.6; margin: 0 0 25px 0;">
                                We received a request to reset your password for your Zinema.lk account. Click the button below to create a new password:
                            </p>
                            
                            <!-- Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding: 15px 0 25px 0;">
                                        <a href="{$resetLink}" style="display: inline-block; background: linear-gradient(135deg, #8a2be2, #ff3366); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: bold; font-size: 16px;">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #cccccc; font-size: 14px; line-height: 1.6; margin: 0 0 10px 0;">
                                ⏰ This link will expire in <strong style="color: #ffffff;">1 hour</strong>.
                            </p>
                            
                            <p style="color: #888888; font-size: 13px; line-height: 1.5; margin: 0;">
                                If you didn't request this password reset, you can safely ignore this email. Your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #141420; padding: 20px; text-align: center; border-top: 1px solid #2a2a3e;">
                            <p style="color: #666666; font-size: 12px; margin: 0;">
                                © {$year} Zinema.lk. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    
    $textBody = "Hi {$username},\n\nWe received a request to reset your password for your Zinema.lk account.\n\nReset your password by visiting:\n{$resetLink}\n\nThis link expires in 1 hour.\n\nIf you didn't request this, you can safely ignore this email.\n\n© {$year} Zinema.lk";
    
    return sendEmail($email, $subject, $htmlBody, $textBody);
}

/**
 * Send email verification email
 * 
 * @param string $email User's email
 * @param string $username User's username
 * @param string $verifyLink Full verification URL with token
 * @return array ['success' => bool, 'message' => string]
 */
function sendVerificationEmail($email, $username, $verifyLink) {
    $subject = "Verify Your Email - Zinema.lk";
    $year = date('Y');
    
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="500" cellspacing="0" cellpadding="0" style="background-color: #1a1a2e; border-radius: 12px; overflow: hidden; max-width: 500px;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #8a2be2, #ff3366); padding: 25px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">🎬 Zinema.lk</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px; background-color: #1a1a2e;">
                            <h2 style="color: #51cf66; margin: 0 0 20px 0; font-size: 22px;">Welcome to Zinema.lk! 🎉</h2>
                            
                            <p style="color: #ffffff; font-size: 16px; line-height: 1.6; margin: 0 0 15px 0;">
                                Hi <strong style="color: #ff3366;">{$username}</strong>,
                            </p>
                            
                            <p style="color: #cccccc; font-size: 15px; line-height: 1.6; margin: 0 0 25px 0;">
                                Thanks for signing up! Please verify your email address to activate your account and start enjoying our content.
                            </p>
                            
                            <!-- Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding: 15px 0 25px 0;">
                                        <a href="{$verifyLink}" style="display: inline-block; background: linear-gradient(135deg, #51cf66, #20c997); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: bold; font-size: 16px;">Verify Email</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #cccccc; font-size: 14px; line-height: 1.6; margin: 0 0 10px 0;">
                                ⏰ This link will expire in <strong style="color: #ffffff;">24 hours</strong>.
                            </p>
                            
                            <p style="color: #888888; font-size: 13px; line-height: 1.5; margin: 0;">
                                If you didn't create an account, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #141420; padding: 20px; text-align: center; border-top: 1px solid #2a2a3e;">
                            <p style="color: #666666; font-size: 12px; margin: 0;">
                                © {$year} Zinema.lk. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    
    $textBody = "Hi {$username},\n\nWelcome to Zinema.lk!\n\nPlease verify your email by visiting:\n{$verifyLink}\n\nThis link expires in 24 hours.\n\nIf you didn't create an account, ignore this email.\n\n© {$year} Zinema.lk";
    
    return sendEmail($email, $subject, $htmlBody, $textBody);
}
