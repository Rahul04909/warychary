<?php
require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create senior_partner_payout_history table
    $sql = "CREATE TABLE IF NOT EXISTS senior_partner_payout_history (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        senior_partner_id INT(11) NOT NULL,
        payout_amount DECIMAL(10,2) NOT NULL,
        previous_earnings DECIMAL(10,2) NOT NULL,
        payout_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payout_method ENUM('bank_transfer', 'upi', 'cash', 'cheque') DEFAULT 'bank_transfer',
        transaction_id VARCHAR(100) NULL,
        bank_name VARCHAR(255) NULL,
        account_number VARCHAR(50) NULL,
        ifsc_code VARCHAR(20) NULL,
        account_holder_name VARCHAR(255) NULL,
        upi_id VARCHAR(100) NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
        notes TEXT NULL,
        processed_by INT(11) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_senior_partner_payout (senior_partner_id),
        INDEX idx_payout_date (payout_date),
        INDEX idx_payout_status (status),
        INDEX idx_payout_method (payout_method)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    echo "Senior partner payout history table created successfully!\n";
    echo "Table structure:\n";
    echo "- id: Primary key\n";
    echo "- senior_partner_id: Reference to senior partner\n";
    echo "- payout_amount: Amount paid out\n";
    echo "- previous_earnings: Earnings before payout\n";
    echo "- payout_date: When payout was made\n";
    echo "- payout_method: Method of payment\n";
    echo "- transaction_id: Bank/UPI transaction reference\n";
    echo "- bank_name, account_number, ifsc_code, account_holder_name: Bank details\n";
    echo "- upi_id: UPI payment details\n";
    echo "- status: Payout status\n";
    echo "- notes: Additional notes\n";
    echo "- processed_by: Admin who processed the payout\n";
    echo "- created_at, updated_at: Timestamps\n";
    echo "\nIndexes created for:\n";
    echo "- senior_partner_id (for partner lookups)\n";
    echo "- payout_date (for date-based queries)\n";
    echo "- status (for status filtering)\n";
    echo "- payout_method (for method filtering)\n";
    
} catch(PDOException $e) {
    echo "Error creating senior partner payout history table: " . $e->getMessage() . "\n";
}
?>