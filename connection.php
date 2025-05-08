<?php
// $servername = "localhost";
// $username = "root";  // Change if needed
// $password = "";      // Change if needed
// $dbname = "projectcamp";

// $conn = new mysqli($servername, $username, $password, $dbname);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
?>
<?php
function getDBConnection() {
    // $host = "mace.mysql.database.azure.com";
    $host = "fundraiser.mysql.database.azure.com";
    $username = "sneha"; // Always use full username
    $password = "sheetal@123";
    $database = "projectcamp";
    $port = 3306;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_init();

    // No SSL setup needed if disabled
    mysqli_real_connect($conn, $host, $username, $password, $database, $port);

    return $conn;
}

$conn = getDBConnection();

if ($conn) {
    // echo "Connected successfully!";
}
?>
