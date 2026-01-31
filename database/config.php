<?php

// Check if PDO extension is loaded
if (!extension_loaded('pdo')) {
    die('PDO extension is not loaded. Please install or enable the PDO extension in your PHP configuration.');
}

// Check if PDO MySQL driver is loaded
if (!extension_loaded('pdo_mysql')) {
    die('PDO MySQL driver is not loaded. Please install or enable the pdo_mysql extension in your PHP configuration.');
}

class Database {
    private $host = "localhost";
    private $db_name = "jhdindus_warychary";
    private $username = "jhdindus_warychary";
    private $password = "Rd14072003@./";
    public $conn;

    private function createPartnersTable() {
        $query = "CREATE TABLE IF NOT EXISTS partners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id VARCHAR(8) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL UNIQUE,
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            image VARCHAR(255) NULL,
            state VARCHAR(255) NOT NULL,
            district VARCHAR(255) NOT NULL,
            pincode VARCHAR(6) NOT NULL,
            full_address TEXT NOT NULL,
            password VARCHAR(255) NOT NULL,
            referral_code VARCHAR(255) NULL,
            earning DECIMAL(10, 2) DEFAULT 0.00,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        try {
            $this->conn->exec($query);
            // Add earning column if it doesn't exist (for existing tables)
            $this->addEarningColumnToPartnersTable();
        } catch(PDOException $e) {
            echo "Table Creation Error: " . $e->getMessage();
        }
    }

    private function addEarningColumnToPartnersTable() {
        // Check if the column already exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :db_name AND table_name = 'partners' AND column_name = 'earning'");
        $stmt->execute([':db_name' => $this->db_name]);
        $columnExists = $stmt->fetchColumn();

        if (!$columnExists) {
            $query = "ALTER TABLE partners ADD COLUMN earning DECIMAL(10, 2) DEFAULT 0.00";
            try {
                $this->conn->exec($query);
            } catch(PDOException $e) {
                error_log("Error adding earning column: " . $e->getMessage());
            }
        }
    }

    public function createUsersTable() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL UNIQUE,
                gender ENUM('male', 'female', 'other') NOT NULL,
                image VARCHAR(255),
                state VARCHAR(50) NOT NULL,
                district VARCHAR(50) NOT NULL,
                pincode VARCHAR(10) NOT NULL,
                full_address TEXT NOT NULL,
                password VARCHAR(255) NOT NULL,
                referral_code VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);
        } catch(PDOException $e) {
            error_log("Error creating users table: " . $e->getMessage());
        }
    }

    public function createSmtpSettingsTable() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS smtp_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                smtp_host VARCHAR(255) NOT NULL,
                smtp_username VARCHAR(255) NOT NULL,
                smtp_password VARCHAR(255) NOT NULL,
                smtp_encryption ENUM('ssl', 'tls') NOT NULL,
                smtp_port INT NOT NULL,
                smtp_from_email VARCHAR(255) NOT NULL,
                smtp_from_name VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);
        } catch(PDOException $e) {
            error_log("Error creating SMTP settings table: " . $e->getMessage());
        }
    }

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->createSmtpSettingsTable();
                $this->createSeniorPartnersTable();
                $this->createPartnersTable();
                $this->createUsersTable(); // Add users table creation
                $this->createPartnerEarningsTable(); // Add partner earnings table creation
                $this->createRazorpaySettingsTable();
                $this->createBankDetailsTable(); // Add bank details table creation
            }
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw $e;
        }
    }

    private function createSeniorPartnersTable() {
        $query = "CREATE TABLE IF NOT EXISTS senior_partners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id VARCHAR(8) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            phone VARCHAR(20),
            image VARCHAR(255) NULL,
            gender ENUM('Male', 'Female', 'Other') NULL,
            state VARCHAR(255) NULL,
            district VARCHAR(255) NULL,
            full_address TEXT NULL,
            password VARCHAR(255) NOT NULL,
            earning DECIMAL(10, 2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        try {
            $this->conn->exec($query);
        } catch(PDOException $e) {
            echo "Table Creation Error: " . $e->getMessage();
        }
    }

    private function addEarningColumnToSeniorPartnersTable() {
        // Check if the column already exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :db_name AND table_name = 'senior_partners' AND column_name = 'earning'");
        $stmt->execute([':db_name' => $this->db_name]);
        $columnExists = $stmt->fetchColumn();

        if (!$columnExists) {
            $query = "ALTER TABLE senior_partners ADD COLUMN earning DECIMAL(10, 2) DEFAULT 0.00";
            try {
                $this->conn->exec($query);
            } catch(PDOException $e) {
                error_log("Error adding earning column: " . $e->getMessage());
            }
        }
    }

    private function createPartnerEarningsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS partner_earnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                partner_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description VARCHAR(255),
                status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
            )";
            $this->conn->exec($sql);
        } catch(PDOException $e) {
            error_log("Error creating partner_earnings table: " . $e->getMessage());
        }
    }

    // Create razorpay_settings table
    private function createRazorpaySettingsTable() {
        $query = "CREATE TABLE IF NOT EXISTS razorpay_settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            razorpay_key_id VARCHAR(255) NOT NULL,
            razorpay_key_secret VARCHAR(255) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->exec($query);
    }

    // Create bank_details table
    private function createBankDetailsTable() {
        $query = "CREATE TABLE IF NOT EXISTS bank_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            bank_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            ifsc_code VARCHAR(20) NOT NULL,
            account_holder_name VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
        )";
        
        try {
            $this->conn->exec($query);
        } catch(PDOException $e) {
            error_log("Error creating bank_details table: " . $e->getMessage());
        }
    }
}
?>