-- SQL script to add tracking_number and courier_name columns to orders table
-- This script adds the missing columns needed for order tracking functionality

-- Add tracking_number column to orders table
ALTER TABLE `orders` 
ADD COLUMN `tracking_number` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tracking number provided by courier service' 
AFTER `razorpay_signature`;

-- Add courier_name column to orders table  
ALTER TABLE `orders` 
ADD COLUMN `courier_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Name of the courier/shipping company'
AFTER `tracking_number`;

-- Add index on tracking_number for faster searches
CREATE INDEX `idx_tracking_number` ON `orders` (`tracking_number`);

-- Add index on courier_name for filtering
CREATE INDEX `idx_courier_name` ON `orders` (`courier_name`);

-- Display success message
SELECT 'Tracking columns added successfully to orders table!' as message;