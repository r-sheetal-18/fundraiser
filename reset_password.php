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
if (!isset($data['email']) || !isset($data['token']) || !isset($data['password']) || 
    empty($data['email']) || empty($data['token']) || empty($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit;
}

$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
$token = $data['token'];
$password = $data['password'];

// Validate email
if (!$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

// Validate password complexity
if (strlen($password) < 8 || 
    !preg_match('/[A-Z]/', $password) || 
    !preg_match('/[a-z]/', $password) || 
    !preg_match('/\d/', $password) || 
    !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    
    echo json_encode([
        'success' => false,
        'message' => 'Password does not meet the requirements.'
    ]);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start a transaction
    $pdo->beginTransaction();
    
    // Get user_id from email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address.'
        ]);
        exit;
    }
    
    // Check if token is valid and not expired - using your table structure
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE user_id = ? AND token = ? AND expiry > NOW()");
    $stmt->execute([$user['user_id'], $token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired token.'
        ]);
        exit;
    }
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user's password - using your table structure
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$hashedPassword, $user['user_id']]);
    
    // Delete the used token
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    
    // Commit the transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Password has been reset successfully.'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error (in a production environment)
    error_log($e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>