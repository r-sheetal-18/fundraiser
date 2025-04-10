
<?php
require_once 'src/Exception.php';
require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // Enable SMTP debugging
            // 0 = off (production), 1 = client, 2 = client and server messages
            $this->mail->SMTPDebug = 0;  

            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'campaignorganizersystem@gmail.com';
            $this->mail->Password   = 'wvpy wqnu kyuv xbdv';  // App Password
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;

            // Sender settings
            $this->mail->setFrom('noreply@campaignorganizer.com', 'Campaign Organizer');
            $this->mail->addReplyTo('noreply@campaignorganizer.com', 'Campaign Organizer Support');
        } catch (Exception $e) {
            // Log configuration error
            error_log("Email Configuration Error: " . $e->getMessage());
        }
    }

    public function sendReceiptEmail($paymentDetails, $pdfPath) {
        try {
            // Reset previous email configurations
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            // Validate email and payment details
            if (empty($paymentDetails['payment_reference'])) {
                error_log("No email address provided");
                return false;
            }

            // Recipients
            $this->mail->addAddress($paymentDetails['payment_reference']);

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Payment Receipt - Transaction #' . $paymentDetails['payment_id'];
            
            // Formatted email body
            $this->mail->Body = sprintf(
                "<html>
                <body>
                    <h2>Thank You for Your Donation</h2>
                    <p>Dear %s,</p>
                    <p>We appreciate your generous contribution of $%.2f.</p>
                    <p>Transaction Details:</p>
                    <ul>
                        <li><strong>Transaction ID:</strong> %s</li>
                        <li><strong>Amount:</strong> $%.2f</li>
                        <li><strong>Date:</strong> %s</li>
                    </ul>
                    <p>Your receipt is attached to this email.</p>
                    <p>Thank you for supporting our cause!</p>
                    <p>Best regards,<br>Campaign Organizer Team</p>
                </body>
                </html>",
                htmlspecialchars($paymentDetails['customer_name']),
                $paymentDetails['amount'],
                htmlspecialchars($paymentDetails['payment_id']),
                $paymentDetails['amount'],
                date('Y-m-d H:i:s')
            );

            // Plain text alternative for non-HTML email clients
            $this->mail->AltBody = sprintf(
                "Thank You for Your Donation\n\n" .
                "Dear %s,\n\n" .
                "We appreciate your generous contribution of $%.2f.\n\n" .
                "Transaction Details:\n" .
                "Transaction ID: %s\n" .
                "Amount: $%.2f\n" .
                "Date: %s\n\n" .
                "Your receipt is attached to this email.\n\n" .
                "Thank you for supporting our cause!\n\n" .
                "Best regards,\nCampaign Organizer Team",
                $paymentDetails['customer_name'],
                $paymentDetails['amount'],
                $paymentDetails['payment_id'],
                $paymentDetails['amount'],
                date('Y-m-d H:i:s')
            );

            // Attach PDF
            if (file_exists($pdfPath)) {
                $this->mail->addAttachment($pdfPath, 'Receipt_' . $paymentDetails['payment_id'] . '.pdf');
            } else {
                error_log("PDF file not found: " . $pdfPath);
                return false;
            }

            // Send email
            $emailSent = $this->mail->send();

            if ($emailSent) {
                error_log("Email sent successfully to " . $paymentDetails['payment_reference']);
            }

            return $emailSent;

        } catch (Exception $e) {
            // Log detailed error information
            error_log("Email Send Error: " . $this->mail->ErrorInfo);
            error_log("Exception: " . $e->getMessage());
            return false;
        }
    }
}
?>