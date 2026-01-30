-- Drop existing partner_earnings table if it exists (to recreate with correct structure)
DROP TABLE IF EXISTS partner_earnings;

-- Create partner_earnings table to store partner earning history
CREATE TABLE partner_earnings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    partner_id INT(11) NOT NULL,
    order_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    earning_amount DECIMAL(10,2) NOT NULL,
    earning_percentage DECIMAL(5,2) NOT NULL,
    order_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create senior_partner_earnings table to store senior partner earning history (if not exists)
CREATE TABLE IF NOT EXISTS senior_partner_earnings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    senior_partner_id INT(11) NOT NULL,
    partner_id INT(11) NOT NULL,
    order_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    earning_amount DECIMAL(10,2) NOT NULL,
    earning_percentage DECIMAL(5,2) NOT NULL,
    order_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (senior_partner_id) REFERENCES senior_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add total_earnings column to partners table (ignore error if column already exists)
ALTER TABLE partners ADD COLUMN total_earnings DECIMAL(10,2) DEFAULT 0.00;

-- Add total_earnings column to senior_partners table (ignore error if column already exists)
ALTER TABLE senior_partners ADD COLUMN total_earnings DECIMAL(10,2) DEFAULT 0.00;