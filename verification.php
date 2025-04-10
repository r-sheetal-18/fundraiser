 <?php  
session_start();
require 'connection.php'; // Include database connection file  

// Sanitize and validate input parameters
$order_id = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : ''; 
$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : ''; 
$customer_name = isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : ''; 
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<script>
            alert('You will receive a PDF receipt after verification.');
            window.location.href = '" . (isset($_SESSION['user_id']) ? 'index.html' : 'home.html') . "';
          </script>";
    exit();
}
?>

<!-- <!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #00008B 0%, #000080 100%);
            margin: 0;
            background-attachment: fixed;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 95%;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: #00008B;
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background-color: #6c757d;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: #00008B;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.1);
        }

        input:read-only, textarea:read-only {
            background-color: #f8f9fa;
            cursor: not-allowed;
            color: #6c757d;
        }

        .btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 25px;
            }

            input, textarea {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Payment</h2>
        <form action="" method="post">
            <input type="text" name="order_id" value="<?php echo $order_id; ?>" placeholder="Order ID" readonly>
            <input type="text" name="transaction_id" placeholder="Transaction ID" required>
            <input type="text" name="amount" value="<?php echo $amount; ?>" placeholder="Amount" readonly>
            <input type="text" name="customer_name" value="<?php echo $customer_name; ?>" placeholder="Customer Name" readonly>
            <textarea name="description" placeholder="Description" readonly><?php echo $description; ?></textarea>
            <button type="submit"  class="btn">Submit Verification</button>
        </form>
    </div>
</body>
</html> -->


<?php 
session_start();
require 'connection.php'; // Include database connection file  

// Sanitize and validate input parameters
$order_id      = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : ''; 
$amount        = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : ''; 
$customer_name = isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : ''; 
$description   = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : ''; 

// Determine if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the submitted transaction id
    $transaction_id = isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : '';

    // If the user is not logged in, we expect an email input for the receipt
    if (!$is_logged_in) {
        $anonymous_email = isset($_POST['anonymous_email']) ? filter_var($_POST['anonymous_email'], FILTER_VALIDATE_EMAIL) : '';
        if (!$anonymous_email) {
            echo "<script>
                    alert('Please enter a valid email address.');
                    window.history.back();
                  </script>";
            exit();
        }
    } else {
        // If logged in, the email should be available in session (set at login)
        $anonymous_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    }
    
    // Define the initial payment status
    $status = 'under_verification';

    // Insert transaction details into the database
    // Adjust the query and binding types if necessary based on your connection settings
    $stmt = $conn->prepare("INSERT INTO transactions (order_id, transaction_id, amount, customer_name, description, email, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // Here amount is assumed to be numeric, customer_name and others are strings.
    $stmt->bind_param("ssissss", $order_id, $transaction_id, $amount, $customer_name, $description, $anonymous_email, $status);
    $stmt->execute();
    $stmt->close();

    // Display different messages and redirect based on user login status
    if ($is_logged_in) {
        echo "<script>
                alert('Your payment is under processing. You will receive a PDF receipt via email.');
                window.location.href = 'index.html';
              </script>";
    } else {
        echo "<script>
                alert('Your donation has been submitted. A PDF receipt will be sent to your email: $anonymous_email');
                window.location.href = 'home.html';
              </script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #00008B 0%, #000080 100%);
            margin: 0;
            background-attachment: fixed;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 95%;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
        }
        h2 {
            color: #00008B;
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
        }
        h2::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background-color: #6c757d;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        input:focus, textarea:focus {
            border-color: #00008B;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.1);
        }
        input:read-only, textarea:read-only {
            background-color: #f8f9fa;
            cursor: not-allowed;
            color: #6c757d;
        }
        .btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }
        .btn:active {
            transform: translateY(0);
        }
        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 25px;
            }
            input, textarea {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Payment</h2>
        <form action="" method="post">
            <input type="text" name="order_id" value="<?php echo $order_id; ?>" placeholder="Order ID" readonly>
            <input type="text" name="transaction_id" placeholder="Transaction ID" required>
            <input type="text" name="amount" value="<?php echo $amount; ?>" placeholder="Amount" readonly>
            <input type="text" name="customer_name" value="<?php echo $customer_name; ?>" placeholder="Customer Name" readonly>
            <textarea name="description" placeholder="Description" readonly><?php echo $description; ?></textarea>
            <?php if (!$is_logged_in): ?>
                <!-- Extra email input for anonymous donations -->
                <input type="email" name="anonymous_email" placeholder="Enter your email for receipt" required>
            <?php endif; ?>
            <button type="submit" class="btn">Submit Verification</button>
        </form>
    </div>
</body>
</html>
