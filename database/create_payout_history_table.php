<?php
require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // SQL to create partner_payout_history table
    $sql = "
    DROP TABLE IF EXISTS `partner_payout_history`;
    CREATE TABLE IF NOT EXISTS `partner_payout_history` (
      `id` int NOT NULL AUTO_INCREMENT,
      `partner_id` int NOT NULL,
      `payout_amount` decimal(10,2) NOT NULL,
      `payout_date` date NOT NULL,
      `payout_month` varchar(7) NOT NULL,
      `earnings_before_payout` decimal(10,2) NOT NULL,
      `earnings_after_payout` decimal(10,2) NOT NULL DEFAULT '0.00',
      `payment_method` enum('bank_transfer','upi','cash','cheque') DEFAULT 'bank_transfer',
      `transaction_reference` varchar(255) DEFAULT NULL,
      `notes` text,
      `processed_by` varchar(255) DEFAULT 'Admin',
      `status` enum('pending','completed','failed') DEFAULT 'completed',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_partner_payout` (`partner_id`,`payout_month`),
      KEY `idx_payout_date` (`payout_date`),
      KEY `idx_payout_month` (`payout_month`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    // Execute the SQL
    $db->exec($sql);
    
    echo "✅ SUCCESS: partner_payout_history table created successfully!\n";
    echo "📊 Table structure:\n";
    echo "   - id (Primary Key)\n";
    echo "   - partner_id (Foreign Key)\n";
    echo "   - payout_amount (Decimal)\n";
    echo "   - payout_date (Date)\n";
    echo "   - payout_month (VARCHAR)\n";
    echo "   - earnings_before_payout (Decimal)\n";
    echo "   - earnings_after_payout (Decimal)\n";
    echo "   - payment_method (ENUM)\n";
    echo "   - transaction_reference (VARCHAR)\n";
    echo "   - notes (TEXT)\n";
    echo "   - processed_by (VARCHAR)\n";
    echo "   - status (ENUM)\n";
    echo "   - created_at (TIMESTAMP)\n";
    echo "   - updated_at (TIMESTAMP)\n";
    echo "\n🔗 Indexes created:\n";
    echo "   - idx_partner_payout (partner_id, payout_month)\n";
    echo "   - idx_payout_date (payout_date)\n";
    echo "   - idx_payout_month (payout_month)\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: Failed to create partner_payout_history table\n";
    echo "Error details: " . $e->getMessage() . "\n";
    exit(1);
}
?>