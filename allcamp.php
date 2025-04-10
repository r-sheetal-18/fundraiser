<?php 
require 'connection.php'; 
session_start();  

if (!isset($_SESSION['user_id'])) {     
    echo "<p>Error: User not logged in.</p>";     
    exit(); 
}  

// Fetch campaigns grouped by status 
$query = "SELECT campaign_id, user_id, title, description, category, goal_amount,                   
           COALESCE(raised_amount, 0.00) AS raised_amount, start_date, end_date, status            
           FROM campaigns            
           ORDER BY FIELD(status, 'Approved', 'Pending', 'Rejected')";  

$result = $conn->query($query);  

// Organize campaigns into different status categories 
$campaigns = [     
    'Approved' => [],     
    'Pending' => [],     
    'Rejected' => [] 
];  

while ($row = $result->fetch_assoc()) {     
    $campaigns[$row['status']][] = $row; 
}  
?>  

<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Campaigns Overview</title>     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">     
    <link rel="stylesheet" href="cstyles6.css"> 
</head> 
<body>     
    <div class="container mt-4">         
        <h2 class="text-center">Campaigns Overview</h2>                  
        <?php foreach ($campaigns as $status => $campaignList): ?>             
            <h3 class="mt-4">                 
                <?= $status ?> Campaigns             
            </h3>             
            <div class="row">                 
                <?php if (!empty($campaignList)): ?>                     
                    <?php foreach ($campaignList as $row): ?>                         
                        <?php                              
                        $progress = ($row['goal_amount'] > 0) ? 
                            ($row['raised_amount'] / $row['goal_amount']) * 100 : 0;                             
                        $start_date = date("d M Y", strtotime($row['start_date']));                             
                        $end_date = date("d M Y", strtotime($row['end_date']));                         
                        ?>                         
                        <div class="col-md-6 col-lg-4 mb-4">                             
                            <div class="campaign-card p-3">                                 
                                <h4><?= htmlspecialchars($row['title']) ?></h4>                                 
                                <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>                                 
                                <p>
                                    <strong>Goal:</strong> ₹<?= number_format($row['goal_amount'], 2) ?> |                                     
                                    <strong>Raised:</strong> ₹<?= number_format($row['raised_amount'], 2) ?>                                 
                                </p>                                 
                                <div class="progress mb-2">                                     
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $progress ?>%"                                           
                                         aria-valuenow="<?= $progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">                                     
                                    </div>                                 
                                </div>                                 
                                <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>                                 
                                <!-- Conditional View Details link based on campaign status -->
                                <?php if ($row['status'] == 'Approved'): ?>
                                    <a href="campdetails.php?id=<?= $row['campaign_id'] ?>" class="btn btn-sm btn-success">
                                        View Approved Details
                                    </a>
                                <?php elseif ($row['status'] == 'Pending'): ?>
                                    <a href="pcampdet.php?id=<?= $row['campaign_id'] ?>" class="btn btn-sm btn-warning">
                                        View Pending Details
                                    </a>
                                <?php endif; ?>
                            </div>                         
                        </div>                     
                    <?php endforeach; ?>                 
                <?php else: ?>                     
                    <p class="text-center">No <?= strtolower($status) ?> campaigns found.</p>                 
                <?php endif; ?>             
            </div>         
        <?php endforeach; ?>     
    </div> 
</body> 
</html>  

<?php $conn->close(); ?>