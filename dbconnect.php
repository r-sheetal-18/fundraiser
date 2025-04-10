<?php
class Database {
    private $conn;

    public function __construct() {
        $host = 'localhost'; 
        $dbname = 'projectcamp';
        $username = 'root'; 
        $password = ''; 

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function updatePaymentStatus($payment_id, $status) {
        try {
            $this->conn->beginTransaction();
            
            // Update payment status
            $stmt = $this->conn->prepare("UPDATE payments SET status = :status WHERE payment_id = :payment_id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':payment_id', $payment_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($status === 'approved') {
                // Retrieve payment details
                $stmt = $this->conn->prepare("SELECT payment_id, campaign_id, amount FROM payments WHERE payment_id = :payment_id");
                $stmt->bindParam(':payment_id', $payment_id, PDO::PARAM_INT);
                $stmt->execute();
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($payment) {
                    // Insert into donations table
                    $stmt = $this->conn->prepare("INSERT INTO donations (payment_id, campaign_id, amount, verified_at) 
                                                  VALUES (:payment_id, :campaign_id, :amount, NOW())");
                    $stmt->bindParam(':payment_id', $payment['payment_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':campaign_id', $payment['campaign_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':amount', $payment['amount']);
                    $stmt->execute();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    public function getPendingPayments() {
        $stmt = $this->conn->prepare("SELECT * FROM payments WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
