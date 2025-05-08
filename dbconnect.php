<?php
class Database {
    private $conn;

    public function __construct() {
        $this->conn = $this->getDBConnection();
    }

    private function getDBConnection() {
        $host = "fundraiser.mysql.database.azure.com";
        $username = "sneha";
        $password = "sheetal@123";
        $database = "projectcamp";
        $port = 3306;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = mysqli_init();

        // Connect to Azure MySQL
        mysqli_real_connect($conn, $host, $username, $password, $database, $port);

        return $conn;
    }

    public function updatePaymentStatus($payment_id, $status) {
        try {
            $this->conn->begin_transaction();

            // Update payment status
            $stmt = $this->conn->prepare("UPDATE payments SET status = ? WHERE payment_id = ?");
            $stmt->bind_param("si", $status, $payment_id);
            $stmt->execute();
            $stmt->close();

            if ($status === 'approved') {
                // Get payment details
                $stmt = $this->conn->prepare("SELECT payment_id, campaign_id, amount FROM payments WHERE payment_id = ?");
                $stmt->bind_param("i", $payment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $payment = $result->fetch_assoc();
                $stmt->close();

                if ($payment) {
                    // Insert into donations
                    $stmt = $this->conn->prepare("INSERT INTO donations (payment_id, campaign_id, amount, verified_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("iid", $payment['payment_id'], $payment['campaign_id'], $payment['amount']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    public function getPendingPayments() {
        $stmt = $this->conn->prepare("SELECT * FROM payments WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $payments;
    }
}
?>
