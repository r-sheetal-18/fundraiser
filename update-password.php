<?php
// Include database connection
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Enable error reporting for debugging
// Comment these lines in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get parameters from the request
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Validate input
    if (empty($token)) {
        $response['message'] = 'Token is required.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($password)) {
        $response['message'] = 'Password is required.';
        echo json_encode($response);
        exit;
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $response['message'] = 'Password must contain at least one uppercase letter.';
        echo json_encode($response);
        exit;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $response['message'] = 'Password must contain at least one lowercase letter.';
        echo json_encode($response);
        exit;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $response['message'] = 'Password must contain at least one number.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if token is valid and not expired
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expiry > NOW()");
        if (!$stmt) {
            throw new Exception("Database error in prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'Invalid or expired token. Please request a new password reset link.';
            echo json_encode($response);
            $conn->rollback();
            exit;
        }
        
        $row = $result->fetch_assoc();
        $userId = $row['user_id'];
        
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the user's password
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_updated_at = NOW() WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database error in prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            // Check if the user exists
            $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $checkUser->bind_param("i", $userId);
            $checkUser->execute();
            $userResult = $checkUser->get_result();
            
            if ($userResult->num_rows === 0) {
                throw new Exception("User not found with ID: $userId");
            } else {
                // The user exists but password didn't change (might be the same password)
                // We'll consider this a success anyway
                $response['success'] = true;
                $response['message'] = 'Your password has been updated successfully.';
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'Your password has been updated successfully.';
        }
        
        // Delete the used token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Database error in prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'An error occurred. Please try again later.';
        // Log the error (in a production environment)
        error_log("Password update error: " . $e->getMessage());
        
        // For debugging in development
        // $response['debug_error'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>