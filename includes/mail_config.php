<?php
// Attempt to load PHPMailer (Composer or manual ZIP) before using it
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $root = dirname(__DIR__);
    $composerAutoload = $root . '/vendor/autoload.php';
    $manualPath1 = $root . '/PHPMailer-master/src';
    $manualPath2 = $root . '/vendor/phpmailer/phpmailer/src';

    if (file_exists($composerAutoload)) {
        require_once $composerAutoload; // Composer install
    } elseif (file_exists($manualPath1 . '/PHPMailer.php')) {
        // Manual ZIP placed in PHPMailer-master
        require_once $manualPath1 . '/Exception.php';
        require_once $manualPath1 . '/PHPMailer.php';
        require_once $manualPath1 . '/SMTP.php';
    } elseif (file_exists($manualPath2 . '/PHPMailer.php')) {
        // Manually copied into vendor/phpmailer/phpmailer
        require_once $manualPath2 . '/Exception.php';
        require_once $manualPath2 . '/PHPMailer.php';
        require_once $manualPath2 . '/SMTP.php';
    }
}

// Email configuration for password reset
// Using PHPMailer for sending emails via Gmail

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_SECURE', 'tls'); // or 'ssl'
define('SMTP_USERNAME', 'lobonathan2209@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'dzhepvytowndzmks'); // Your Gmail App Password (not regular password)
define('SMTP_FROM_EMAIL', 'lobonathan2209@gmail.com'); // From email address
define('SMTP_FROM_NAME', "CodeGram"); // From name

// Base URL of your application
define('BASE_URL', 'http://localhost/Nathan/wd-project'); // Update this to your actual URL

/**
 * Send password reset email
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name
 * @param string $reset_link Password reset link
 * @return bool Success status
 */
function send_password_reset_email($to_email, $to_name, $reset_link) {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback to basic mail() function (less reliable)
        return send_basic_email($to_email, $to_name, $reset_link);
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - CodeGram';
        $mail->Body    = get_reset_email_html($to_name, $reset_link);
        $mail->AltBody = get_reset_email_text($to_name, $reset_link);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send OTP for email verification
 */
function send_otp_email($to_email, $to_name, $code) {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        // Basic fallback
        $subject = 'Your CodeGram verification code';
        $message = "Hi $to_name,\n\nYour verification code is: $code\nIt will expire in 10 minutes.\n\nIf you didn't try to sign up, you can ignore this email.";
        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n" .
                   'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        return mail($to_email, $subject, $message, $headers);
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = 'Verify your email - CodeGram';
        $mail->Body    = '<p>Hi ' . htmlspecialchars($to_name) . ',</p>' .
                         '<p>Your verification code is:</p>' .
                         '<p style="font-size:28px;font-weight:700;letter-spacing:4px">' . htmlspecialchars($code) . '</p>' .
                         '<p>This code expires in 10 minutes.</p>' .
                         '<p>If you did not try to sign up, you can ignore this email.</p>';
        $mail->AltBody = "Your verification code is: $code (expires in 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Fallback function using basic mail()
 */
function send_basic_email($to_email, $to_name, $reset_link) {
    $subject = 'Password Reset Request - LOBO\'s Development';
    $message = get_reset_email_text($to_name, $reset_link);
    $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n" .
               'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    return mail($to_email, $subject, $message, $headers);
}

/**
 * Get HTML email template
 */
function get_reset_email_html($name, $reset_link) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #373737; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 30px; }
            .button { display: inline-block; padding: 12px 30px; background-color: #373737; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Password Reset Request</h1>
            </div>
            <div class="content">
                <p>Hi ' . htmlspecialchars($name) . ',</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($reset_link) . '" class="button">Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #007bff;">' . htmlspecialchars($reset_link) . '</p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you didn\'t request a password reset, please ignore this email or contact support if you have concerns.</p>
            </div>
            <div class="footer">
                <p>&copy; 2025 LOBO\'s Development. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get plain text email template
 */
function get_reset_email_text($name, $reset_link) {
    return "Hi $name,\n\n" .
           "We received a request to reset your password.\n\n" .
           "Click the link below to create a new password:\n" .
           "$reset_link\n\n" .
           "This link will expire in 1 hour.\n\n" .
           "If you didn't request a password reset, please ignore this email.\n\n" .
           "Best regards,\n" .
           "LOBO's Development Team";
}
?>
