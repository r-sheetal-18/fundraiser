<?php
// Set headers for JSON response
header("Content-Type: application/json");

// Database connection settings
$db_host = 'localhost';
$db_name = 'projectcamp';
$db_user = 'root';
$db_pass = '';

// Get request data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if required data is provided
if (!isset($data['email']) || !isset($data['token']) || empty($data['email']) || empty($data['token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and verification code are required.'
    ]);
    exit;
}

$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
$token = trim($data['token']); // Trim whitespace from token

if (!$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user_id from email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address.'
        ]);
        exit;
    }
    
    // For debugging purposes - log the submitted data and what's in the database
    // Remove in production
    $debug_stmt = $pdo->prepare("SELECT token, expiry FROM password_resets WHERE user_id = ?");
    $debug_stmt->execute([$user['user_id']]);
    $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Verification attempt - Email: $email, Submitted token: $token");
    error_log("Database info - Token: " . ($debug_info['token'] ?? 'none') . ", Expiry: " . ($debug_info['expiry'] ?? 'none'));
    
    // Check if token is valid and not expired - using your table structure
    // Note: Making the token comparison case-insensitive with UPPER()
    $stmt = $pdo->prepare("SELECT * FROM password_resets 
                           WHERE user_id = ? 
                           AND UPPER(token) = UPPER(?) 
                           AND expiry > NOW()");
    $stmt->execute([$user['user_id'], $token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired verification code.'
        ]);
        exit;
    }
    
    // Generate a secure token for the password reset process
    $reset_token = bin2hex(random_bytes(16));
    
    // Store the reset token for the next step (optional)
    $stmt = $pdo->prepare("UPDATE password_resets 
                           SET reset_token = ? 
                           WHERE user_id = ?");
    $stmt->execute([$reset_token, $user['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification successful.',
        'reset_token' => $reset_token,
        'user_id' => $user['user_id']
    ]);
    
} catch (PDOException $e) {
    // Log error (in a production environment)
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>