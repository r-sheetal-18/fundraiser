<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "projectcamp";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update raised amount for each campaign
$updateSql = "
    UPDATE campaigns c
    JOIN (
        SELECT campaign_id, SUM(amount) AS total_raised
        FROM donations
        GROUP BY campaign_id
    ) d ON c.campaign_id = d.campaign_id
    SET c.raised_amount = d.total_raised
";
$conn->query($updateSql);

// Check if any campaign has met the goal amount and update status
$checkStatusSql = "
    UPDATE campaigns
    SET status = 'Completed'
    WHERE raised_amount >= goal_amount AND status != 'Completed'
";
$conn->query($checkStatusSql);

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch updated donations with campaign details and search filter
$sql = "
    SELECT d.donation_id, d.amount, d.verified_at AS donated_at, 
           c.campaign_id, c.title, c.description, c.category, 
           c.goal_amount, c.raised_amount, c.start_date, c.end_date, c.status 
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.campaign_id
    WHERE c.title LIKE '%$search%' OR c.category LIKE '%$search%'
";

$result = $conn->query($sql);

$donations = [];
while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Donations</title>
    <link rel="stylesheet" href="cstylesd.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 20px;
    background-color: #f4f4f4;
}

h2 {
    color: #333;
    text-align: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
    background-color: white;
}

table th {
    background-color: #007bff;
    color: white;
    font-weight: bold;
    padding: 12px;
    text-align: left;
    text-transform: uppercase;
}

table tr:nth-child(even) {
    background-color: #f2f2f2;
}

table tr:hover {
    background-color: #e6e6e6;
    transition: background-color 0.3s ease;
}

table td {
    padding: 10px;
    border: 1px solid #ddd;
    color: #333;
}

/* Responsive design for smaller screens */
@media screen and (max-width: 768px) {
    table {
        font-size: 14px;
    }

    table th, table td {
        padding: 8px;
    }
}
/* Search Form Styling */
.search-container {
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
}

.search-container input[type="text"] {
    padding: 10px;
    width: 300px;
    max-width: 80%;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    transition: border 0.3s ease;
}

.search-container input[type="text"]:focus {
    border-color: #007bff;
    outline: none;
}

.search-container input[type="submit"] {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    margin-left: 8px;
    transition: background-color 0.3s ease;
}

.search-container input[type="submit"]:hover {
    background-color: #0056b3;
}

        /* body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 8px;
            width: 300px;
            font-size: 14px;
        }
        input[type="submit"] {
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
        }
        table th {
            background-color: #f2f2f2;
        } */
    </style>
</head>
<body>
    <h2>All Donations</h2>

    <!-- Search Form -->
    <div class="search-container">
    <form method="get" action="">
        <input type="text" name="search" placeholder="Search by title or category..." value="<?= htmlspecialchars($search) ?>">
        <input type="submit" value="Search">
    </form>
</div>


    <table border="1">
        <tr>
            <th>Donation ID</th>
            <th>Campaign Title</th>
            <th>Category</th>
            <th>Goal Amount</th>
            <th>Raised Amount</th>
            <th>Donated Amount</th>
            <th>Donation Date</th>
            <th>Campaign Status</th>
        </tr>
        <?php if (count($donations) > 0): ?>
            <?php foreach ($donations as $donation): ?>
                <tr>
                    <td><?= htmlspecialchars($donation['donation_id']) ?></td>
                    <td><?= htmlspecialchars($donation['title']) ?></td>
                    <td><?= htmlspecialchars($donation['category']) ?></td>
                    <td><?= htmlspecialchars($donation['goal_amount']) ?></td>
                    <td><?= htmlspecialchars($donation['raised_amount']) ?></td>
                    <td><?= htmlspecialchars($donation['amount']) ?></td>
                    <td><?= htmlspecialchars($donation['donated_at']) ?></td>
                    <td><?= htmlspecialchars($donation['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No donations found.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>
