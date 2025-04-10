<?php
// includes/generate_receipt.php
require 'fpdf.php';

//use setasign\Fpdf\Fpdf;

class ReceiptGenerator extends FPDF {
    private $paymentDetails;

    public function __construct($paymentDetails) {
        parent::__construct();
        $this->paymentDetails = $paymentDetails;
    }

    public function generatePDF() {
        $this->AddPage();
        
        // Header
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Payment Receipt', 0, 1, 'C');
        
        // Payment Details
        $this->SetFont('Arial', '', 12);
        $this->Ln(10);
        
        $this->Cell(60, 10, 'Payment ID:', 0, 0);
        $this->Cell(0, 10, $this->paymentDetails['payment_id'], 0, 1);
        
        $this->Cell(60, 10, 'Customer Name:', 0, 0);
        $this->Cell(0, 10, $this->paymentDetails['customer_name'], 0, 1);
        
        $this->Cell(60, 10, 'Amount:', 0, 0);
        $this->Cell(0, 10, '$' . number_format($this->paymentDetails['amount'], 2), 0, 1);
        
        $this->Cell(60, 10, 'Description:', 0, 0);
        $this->Cell(0, 10, $this->paymentDetails['description'] ?? 'N/A', 0, 1);
        
        $this->Cell(60, 10, 'Payment Date:', 0, 0);
        $this->Cell(0, 10, date('Y-m-d H:i:s', strtotime($this->paymentDetails['created_at'])), 0, 1);
        
        // Footer
        $this->Ln(20);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, 'Thank you for your support!', 0, 1, 'C');

        // Generate filename
        $filename = 'receipt_' . $this->paymentDetails['payment_id'] . '.pdf';
        $this->Output('F', 'receipts/' . $filename);

        return $filename;
    }
}
?>