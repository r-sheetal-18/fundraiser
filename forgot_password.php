<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set headers for JSON response
header("Content-Type: application/json");

// Use statements must come first!
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

// Database connection settings
$db_host = 'localhost';
$db_name = 'projectcamp';
$db_user = 'root';
$db_pass = ''; 

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Check if email was provided
    if (!isset($data['email']) || empty($data['email'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email address is required.'
        ]);
        exit;
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);

    // Validate email format
    if (!$email) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format.'
        ]);
        exit;
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT user_id, email, username, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal that email doesn't exist for security reasons
        echo json_encode([
            'success' => true,
            'message' => 'If your email is registered, you will receive a verification code shortly.'
        ]);
        exit;
    }
    
    // Generate a 5-letter verification code
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed similar looking characters
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    // Set expiration time (1 hour from now)
    $expiry = date('Y-m-d H:i:s', time() + 3600);
    
    // Store code in the database
    // First check if a token already exists for this user
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing token
        $stmt = $pdo->prepare("UPDATE password_resets SET token = ?, expiry = ?, created_at = NOW() WHERE user_id = ?");
        $stmt->execute([$code, $expiry, $user['user_id']]);
    } else {
        // Insert new token
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expiry, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user['user_id'], $code, $expiry]);
    }
    
    // Send the email with the verification code
    $mail = new PHPMailer(true);
    
    // Server settings - configure these with your SMTP provider details
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'campaignorganizersystem@gmail.com';
    $mail->Password   = 'wvpy wqnu kyuv xbdv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Recipients
    $mail->setFrom('noreply@campaignorganizersystem.com', 'Campaign Organizer');
    $mail->addAddress($user['email'], $user['full_name'] ?? $user['username']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Verification Code';
    
    // Email body
    $name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
    
    $htmlBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #1a3a5f; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .code { font-size: 24px; font-weight: bold; background-color: #e9ecef; padding: 10px; 
                     text-align: center; letter-spacing: 5px; margin: 20px 0; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset</h2>
                </div>
                <div class='content'>
                    <p>Hello {$name},</p>
                    <p>We received a request to reset your password. Use the verification code below to complete the process:</p>
                    
                    <div class='code'>{$code}</div>
                    
                    <p>This code will expire in 1 hour.</p>
                    <p>If you didn't request a password reset, you can safely ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email, please do not reply.</p>
                    <p>&copy; " . date('Y') . " Campaign Organizer System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $textBody = "Hello {$name},\n\n" .
               "We received a request to reset your password. " .
               "Use the verification code below to complete the process:\n\n" .
               "{$code}\n\n" .
               "This code will expire in 1 hour.\n\n" .
               "If you didn't request a password reset, you can safely ignore this email.\n\n" .
               "This is an automated email, please do not reply.\n" .
               "© " . date('Y') . " Campaign Organizer System. All rights reserved.";
    
    $mail->Body    = $htmlBody;
    $mail->AltBody = $textBody;
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'A verification code has been sent to your email address.'
    ]);
    
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
} catch (Exception $e) {
    // Log error but don't expose details
    error_log("Email error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send verification code. Please try again later.'
    ]);
}
?>