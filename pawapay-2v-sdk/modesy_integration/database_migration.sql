-- ============================================================================
-- MODESY MARKETPLACE - PAWAPAY INTEGRATION DATABASE MIGRATION
-- ============================================================================
-- This migration script ensures the payment_gateways table has all required
-- fields for PawaPay integration as per Modesy script documentation.
--
-- Usage:
-- 1. Backup your database before running this migration
-- 2. Execute this script in your MySQL/MariaDB environment
-- 3. Verify the changes using the verification queries at the end
-- ============================================================================

-- Create or modify payment_gateways table to match Modesy requirements
CREATE TABLE IF NOT EXISTS `payment_gateways` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `payment_option` varchar(100) NOT NULL DEFAULT '',
    `name_key` varchar(100) NOT NULL DEFAULT '',
    `public_key` text,
    `secret_key` text,
    `environment` varchar(20) NOT NULL DEFAULT 'production',
    `status` tinyint(1) NOT NULL DEFAULT '1',
    `base_currency` varchar(10) NOT NULL DEFAULT 'USD',
    `allowed_currencies` text,
    `extra_config` text,
    `webhook_url` text,
    `redirect_urls` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payment_option_unique` (`payment_option`),
    KEY `idx_payment_option_status` (`payment_option`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add specific columns if they don't exist (for existing installations)
SET @dbname = DATABASE();

-- Check and add public_key column
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "public_key"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `public_key` text AFTER `name_key`',
    'SELECT "public_key column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add secret_key column
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "secret_key"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `secret_key` text AFTER `public_key`',
    'SELECT "secret_key column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add environment column
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "environment"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `environment` varchar(20) NOT NULL DEFAULT "production" AFTER `secret_key`',
    'SELECT "environment column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add extra_config column for PawaPay specific configuration
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "extra_config"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `extra_config` text AFTER `allowed_currencies`',
    'SELECT "extra_config column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add webhook_url column
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "webhook_url"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `webhook_url` text AFTER `extra_config`',
    'SELECT "webhook_url column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add redirect_urls column
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "payment_gateways" AND COLUMN_NAME = "redirect_urls"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment_gateways ADD COLUMN `redirect_urls` text AFTER `webhook_url`',
    'SELECT "redirect_urls column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert or update PawaPay gateway configuration
INSERT INTO `payment_gateways` (
    `payment_option`, 
    `name_key`, 
    `public_key`, 
    `secret_key`, 
    `environment`, 
    `status`, 
    `base_currency`, 
    `allowed_currencies`, 
    `extra_config`,
    `webhook_url`,
    `redirect_urls`
) VALUES (
    'pawapay',
    'pawapay',
    '', -- To be configured in admin panel
    '', -- To be configured in admin panel
    'sandbox', -- Default to sandbox for testing
    1, -- Enabled
    'USD',
    'USD,UGX,KES,TZS,RWF,ZMW,MWK,XAF,XOF', -- Common African currencies
    '{"reconciliation_enabled":true,"auto_capture":true,"webhook_retry_attempts":3,"payment_timeout":900}',
    '', -- To be configured based on your domain
    '{"success_url":"","cancel_url":"","failure_url":""}' -- To be configured based on your domain
) ON DUPLICATE KEY UPDATE
    `name_key` = VALUES(`name_key`),
    `base_currency` = VALUES(`base_currency`),
    `allowed_currencies` = VALUES(`allowed_currencies`),
    `extra_config` = VALUES(`extra_config`),
    `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================================
-- ORDERS AND TRANSACTIONS TABLES ENHANCEMENTS
-- ============================================================================

-- Ensure orders table has PawaPay specific fields
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "orders" AND COLUMN_NAME = "payment_gateway_transaction_id"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN `payment_gateway_transaction_id` varchar(255) DEFAULT NULL AFTER `payment_method`',
    'SELECT "payment_gateway_transaction_id column already exists in orders" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment gateway reference
SET @sql = CONCAT('SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = "', @dbname, '" AND TABLE_NAME = "orders" AND COLUMN_NAME = "payment_gateway_reference"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN `payment_gateway_reference` text DEFAULT NULL AFTER `payment_gateway_transaction_id`',
    'SELECT "payment_gateway_reference column already exists in orders" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create transactions table for detailed payment tracking
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `transaction_id` varchar(255) NOT NULL,
    `gateway_transaction_id` varchar(255) DEFAULT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'pawapay',
    `transaction_type` enum('payment','refund','commission') NOT NULL DEFAULT 'payment',
    `amount` decimal(10,2) NOT NULL,
    `currency` varchar(10) NOT NULL DEFAULT 'USD',
    `status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    `gateway_response` text,
    `webhook_data` text,
    `reconciliation_status` enum('not_required','pending','completed','failed') DEFAULT 'not_required',
    `reconciliation_attempts` int(11) DEFAULT 0,
    `last_reconciliation_attempt` timestamp NULL DEFAULT NULL,
    `vendor_id` int(11) DEFAULT NULL,
    `commission_amount` decimal(10,2) DEFAULT 0.00,
    `commission_rate` decimal(5,2) DEFAULT 0.00,
    `notes` text,
    `metadata` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_id_unique` (`transaction_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
    KEY `idx_status` (`status`),
    KEY `idx_reconciliation_status` (`reconciliation_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_vendor_id` (`vendor_id`),
    CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- WEBHOOK TRACKING TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `webhook_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` varchar(255) NOT NULL,
    `gateway` varchar(50) NOT NULL DEFAULT 'pawapay',
    `webhook_type` varchar(50) NOT NULL,
    `raw_payload` text NOT NULL,
    `signature` varchar(255) DEFAULT NULL,
    `signature_verified` tinyint(1) DEFAULT 0,
    `processed` tinyint(1) DEFAULT 0,
    `processing_attempts` int(11) DEFAULT 0,
    `last_processing_attempt` timestamp NULL DEFAULT NULL,
    `error_message` text,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_gateway` (`gateway`),
    KEY `idx_processed` (`processed`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Run these queries after migration to verify successful setup

SELECT 'Database Migration Completed Successfully!' as status;

-- Verify payment_gateways table structure
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'payment_gateways' 
ORDER BY ORDINAL_POSITION;

-- Verify PawaPay gateway entry
SELECT 
    payment_option,
    name_key,
    environment,
    status,
    base_currency,
    allowed_currencies,
    created_at
FROM payment_gateways 
WHERE payment_option = 'pawapay';

-- Verify transactions table
SELECT 
    'transactions' as table_name,
    COUNT(*) as column_count
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'transactions';

-- Verify webhook_logs table
SELECT 
    'webhook_logs' as table_name,
    COUNT(*) as column_count
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'webhook_logs';

-- Show indexes for performance
SHOW INDEX FROM payment_gateways;
SHOW INDEX FROM transactions;
SHOW INDEX FROM webhook_logs;