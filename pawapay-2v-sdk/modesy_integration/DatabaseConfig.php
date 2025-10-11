<?php

/**
 * ============================================================================
 * MODESY MARKETPLACE - PAWAPAY DATABASE CONFIGURATION CLASS
 * ============================================================================
 * 
 * This class provides database configuration and management utilities
 * specifically designed for Modesy marketplace integration with PawaPay.
 * 
 * Features:
 * - Modesy database connection management
 * - Payment gateway configuration retrieval
 * - Transaction logging and status updates
 * - Webhook logging with signature verification
 * - Multi-vendor commission calculations
 * - Automated reconciliation support
 * 
 * Usage:
 * $dbConfig = new DatabaseConfig($modesy_db_config);
 * $gateway = $dbConfig->getPaymentGatewayConfig('pawapay');
 * 
 * @author PawaPay SDK Integration Team
 * @version 2.0.0
 * @since 2024-10-11
 * ============================================================================
 */

namespace PawaPay\Service;

use PDO;
use PDOException;
use Exception;

class DatabaseConfig
{
    private $connection;
    private $config;
    private $tablePrefixes;
    
    // Default Modesy table configuration
    private const DEFAULT_TABLES = [
        'payment_gateways' => 'payment_gateways',
        'orders' => 'orders',
        'transactions' => 'transactions', 
        'webhook_logs' => 'webhook_logs',
        'users' => 'users',
        'vendors' => 'vendors',
        'order_products' => 'order_products',
        'products' => 'products'
    ];
    
    /**
     * Initialize database configuration for Modesy integration
     * 
     * @param array $config Database configuration array matching Modesy format
     * @throws Exception If configuration is invalid
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        $this->tablePrefixes = $config['table_prefixes'] ?? [];
        $this->connect();
    }
    
    /**
     * Validate required database configuration parameters
     * 
     * @param array $config Configuration to validate
     * @throws Exception If required parameters are missing
     */
    private function validateConfig(array $config): void
    {
        $required = ['host', 'database', 'username', 'password'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception("Database configuration missing required field: {$field}");
            }
        }
        
        // Set default port if not provided
        if (empty($config['port'])) {
            $config['port'] = 3306;
        }
        
        // Set default charset if not provided
        if (empty($config['charset'])) {
            $config['charset'] = 'utf8mb4';
        }
    }
    
    /**
     * Establish database connection using Modesy-compatible PDO
     * 
     * @throws Exception If connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->config['charset']
            ];
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get table name with prefix support for Modesy installations
     * 
     * @param string $tableName Base table name
     * @return string Full table name with prefix
     */
    private function getTableName(string $tableName): string
    {
        $prefix = $this->tablePrefixes[$tableName] ?? '';
        return $prefix . (self::DEFAULT_TABLES[$tableName] ?? $tableName);
    }
    
    /**
     * Retrieve PawaPay gateway configuration from Modesy database
     * 
     * @param string $paymentOption Payment option identifier (default: 'pawapay')
     * @return array|null Gateway configuration or null if not found
     * @throws Exception If database query fails
     */
    public function getPaymentGatewayConfig(string $paymentOption = 'pawapay'): ?array
    {
        try {
            $tableName = $this->getTableName('payment_gateways');
            
            $stmt = $this->connection->prepare("
                SELECT 
                    id,
                    payment_option,
                    name_key,
                    public_key,
                    secret_key,
                    environment,
                    status,
                    base_currency,
                    allowed_currencies,
                    extra_config,
                    webhook_url,
                    redirect_urls,
                    created_at,
                    updated_at
                FROM {$tableName} 
                WHERE payment_option = :payment_option 
                    AND status = 1
                LIMIT 1
            ");
            
            $stmt->execute(['payment_option' => $paymentOption]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Parse JSON fields
                $result['allowed_currencies'] = $result['allowed_currencies'] 
                    ? explode(',', $result['allowed_currencies']) 
                    : [];
                    
                $result['extra_config'] = $result['extra_config'] 
                    ? json_decode($result['extra_config'], true) 
                    : [];
                    
                $result['redirect_urls'] = $result['redirect_urls'] 
                    ? json_decode($result['redirect_urls'], true) 
                    : [];
            }
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to retrieve gateway configuration: " . $e->getMessage());
        }
    }
    
    /**
     * Update payment gateway configuration
     * 
     * @param string $paymentOption Payment option identifier
     * @param array $config Configuration array to update
     * @return bool Success status
     * @throws Exception If update fails
     */
    public function updatePaymentGatewayConfig(string $paymentOption, array $config): bool
    {
        try {
            $tableName = $this->getTableName('payment_gateways');
            
            $setClause = [];
            $params = ['payment_option' => $paymentOption];
            
            foreach ($config as $key => $value) {
                if (in_array($key, ['public_key', 'secret_key', 'environment', 'status', 'base_currency', 'webhook_url'])) {
                    $setClause[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
            }
            
            if (isset($config['allowed_currencies']) && is_array($config['allowed_currencies'])) {
                $setClause[] = "allowed_currencies = :allowed_currencies";
                $params['allowed_currencies'] = implode(',', $config['allowed_currencies']);
            }
            
            if (isset($config['extra_config']) && is_array($config['extra_config'])) {
                $setClause[] = "extra_config = :extra_config";
                $params['extra_config'] = json_encode($config['extra_config']);
            }
            
            if (isset($config['redirect_urls']) && is_array($config['redirect_urls'])) {
                $setClause[] = "redirect_urls = :redirect_urls";
                $params['redirect_urls'] = json_encode($config['redirect_urls']);
            }
            
            if (empty($setClause)) {
                return false;
            }
            
            $setClause[] = "updated_at = NOW()";
            
            $sql = "UPDATE {$tableName} SET " . implode(', ', $setClause) . " WHERE payment_option = :payment_option";
            
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            throw new Exception("Failed to update gateway configuration: " . $e->getMessage());
        }
    }
    
    /**
     * Create or update transaction record in Modesy database
     * 
     * @param array $transactionData Transaction data array
     * @return string Transaction ID
     * @throws Exception If transaction creation fails
     */
    public function createTransaction(array $transactionData): string
    {
        try {
            $tableName = $this->getTableName('transactions');
            
            $transactionId = $transactionData['transaction_id'] ?? $this->generateTransactionId();
            
            $stmt = $this->connection->prepare("
                INSERT INTO {$tableName} (
                    order_id,
                    user_id,
                    transaction_id,
                    gateway_transaction_id,
                    payment_method,
                    transaction_type,
                    amount,
                    currency,
                    status,
                    gateway_response,
                    vendor_id,
                    commission_amount,
                    commission_rate,
                    notes,
                    metadata
                ) VALUES (
                    :order_id,
                    :user_id,
                    :transaction_id,
                    :gateway_transaction_id,
                    :payment_method,
                    :transaction_type,
                    :amount,
                    :currency,
                    :status,
                    :gateway_response,
                    :vendor_id,
                    :commission_amount,
                    :commission_rate,
                    :notes,
                    :metadata
                )
                ON DUPLICATE KEY UPDATE
                    gateway_transaction_id = VALUES(gateway_transaction_id),
                    status = VALUES(status),
                    gateway_response = VALUES(gateway_response),
                    notes = VALUES(notes),
                    metadata = VALUES(metadata),
                    updated_at = NOW()
            ");
            
            $params = [
                'order_id' => $transactionData['order_id'],
                'user_id' => $transactionData['user_id'] ?? null,
                'transaction_id' => $transactionId,
                'gateway_transaction_id' => $transactionData['gateway_transaction_id'] ?? null,
                'payment_method' => $transactionData['payment_method'] ?? 'pawapay',
                'transaction_type' => $transactionData['transaction_type'] ?? 'payment',
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency'] ?? 'USD',
                'status' => $transactionData['status'] ?? 'pending',
                'gateway_response' => isset($transactionData['gateway_response']) 
                    ? json_encode($transactionData['gateway_response']) : null,
                'vendor_id' => $transactionData['vendor_id'] ?? null,
                'commission_amount' => $transactionData['commission_amount'] ?? 0.00,
                'commission_rate' => $transactionData['commission_rate'] ?? 0.00,
                'notes' => $transactionData['notes'] ?? null,
                'metadata' => isset($transactionData['metadata']) 
                    ? json_encode($transactionData['metadata']) : null
            ];
            
            $stmt->execute($params);
            
            return $transactionId;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to create transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Update transaction status and related data
     * 
     * @param string $transactionId Transaction ID to update
     * @param array $updateData Data to update
     * @return bool Success status
     * @throws Exception If update fails
     */
    public function updateTransaction(string $transactionId, array $updateData): bool
    {
        try {
            $tableName = $this->getTableName('transactions');
            
            $setClause = [];
            $params = ['transaction_id' => $transactionId];
            
            foreach ($updateData as $key => $value) {
                if (in_array($key, ['status', 'gateway_transaction_id', 'notes', 'reconciliation_status', 'reconciliation_attempts'])) {
                    $setClause[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
            }
            
            if (isset($updateData['gateway_response'])) {
                $setClause[] = "gateway_response = :gateway_response";
                $params['gateway_response'] = json_encode($updateData['gateway_response']);
            }
            
            if (isset($updateData['metadata'])) {
                $setClause[] = "metadata = :metadata";
                $params['metadata'] = json_encode($updateData['metadata']);
            }
            
            if (empty($setClause)) {
                return false;
            }
            
            $setClause[] = "updated_at = NOW()";
            
            if (isset($updateData['reconciliation_status'])) {
                $setClause[] = "last_reconciliation_attempt = NOW()";
            }
            
            $sql = "UPDATE {$tableName} SET " . implode(', ', $setClause) . " WHERE transaction_id = :transaction_id";
            
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            throw new Exception("Failed to update transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Log webhook data for processing and audit trails
     * 
     * @param array $webhookData Webhook data to log
     * @return int Webhook log ID
     * @throws Exception If logging fails
     */
    public function logWebhook(array $webhookData): int
    {
        try {
            $tableName = $this->getTableName('webhook_logs');
            
            $stmt = $this->connection->prepare("
                INSERT INTO {$tableName} (
                    transaction_id,
                    gateway,
                    webhook_type,
                    raw_payload,
                    signature,
                    signature_verified,
                    processed,
                    ip_address,
                    user_agent
                ) VALUES (
                    :transaction_id,
                    :gateway,
                    :webhook_type,
                    :raw_payload,
                    :signature,
                    :signature_verified,
                    :processed,
                    :ip_address,
                    :user_agent
                )
            ");
            
            $params = [
                'transaction_id' => $webhookData['transaction_id'],
                'gateway' => $webhookData['gateway'] ?? 'pawapay',
                'webhook_type' => $webhookData['webhook_type'] ?? 'payment_status',
                'raw_payload' => json_encode($webhookData['payload']),
                'signature' => $webhookData['signature'] ?? null,
                'signature_verified' => $webhookData['signature_verified'] ?? 0,
                'processed' => $webhookData['processed'] ?? 0,
                'ip_address' => $webhookData['ip_address'] ?? null,
                'user_agent' => $webhookData['user_agent'] ?? null
            ];
            
            $stmt->execute($params);
            
            return (int) $this->connection->lastInsertId();
            
        } catch (PDOException $e) {
            throw new Exception("Failed to log webhook: " . $e->getMessage());
        }
    }
    
    /**
     * Get Modesy order details with related products and vendor information
     * 
     * @param int $orderId Order ID
     * @return array|null Order data or null if not found
     * @throws Exception If query fails
     */
    public function getOrderDetails(int $orderId): ?array
    {
        try {
            $ordersTable = $this->getTableName('orders');
            $productsTable = $this->getTableName('order_products');
            $usersTable = $this->getTableName('users');
            $vendorsTable = $this->getTableName('vendors');
            
            $stmt = $this->connection->prepare("
                SELECT 
                    o.*,
                    u.email as customer_email,
                    u.first_name as customer_first_name,
                    u.last_name as customer_last_name,
                    COUNT(op.id) as product_count,
                    GROUP_CONCAT(DISTINCT op.vendor_id) as vendor_ids
                FROM {$ordersTable} o
                LEFT JOIN {$usersTable} u ON o.buyer_id = u.id
                LEFT JOIN {$productsTable} op ON o.id = op.order_id
                WHERE o.id = :order_id
                GROUP BY o.id
                LIMIT 1
            ");
            
            $stmt->execute(['order_id' => $orderId]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Get order products with vendor details
                $stmt = $this->connection->prepare("
                    SELECT 
                        op.*,
                        p.title as product_title,
                        v.shop_name as vendor_name,
                        v.commission_rate as vendor_commission_rate
                    FROM {$productsTable} op
                    LEFT JOIN {$this->getTableName('products')} p ON op.product_id = p.id
                    LEFT JOIN {$vendorsTable} v ON op.vendor_id = v.id
                    WHERE op.order_id = :order_id
                ");
                
                $stmt->execute(['order_id' => $orderId]);
                $order['products'] = $stmt->fetchAll();
                
                // Parse vendor IDs
                $order['vendor_ids'] = $order['vendor_ids'] ? explode(',', $order['vendor_ids']) : [];
            }
            
            return $order ?: null;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to get order details: " . $e->getMessage());
        }
    }
    
    /**
     * Get transactions pending reconciliation (older than specified minutes)
     * 
     * @param int $minutesOld Minimum age in minutes for reconciliation
     * @param int $limit Maximum number of transactions to return
     * @return array Array of transactions needing reconciliation
     * @throws Exception If query fails
     */
    public function getTransactionsPendingReconciliation(int $minutesOld = 15, int $limit = 100): array
    {
        try {
            $tableName = $this->getTableName('transactions');
            
            $stmt = $this->connection->prepare("
                SELECT 
                    transaction_id,
                    gateway_transaction_id,
                    order_id,
                    amount,
                    currency,
                    status,
                    reconciliation_attempts,
                    created_at
                FROM {$tableName}
                WHERE status IN ('pending', 'processing')
                    AND (reconciliation_status IS NULL OR reconciliation_status = 'pending')
                    AND reconciliation_attempts < 5
                    AND created_at < DATE_SUB(NOW(), INTERVAL :minutes_old MINUTE)
                    AND payment_method = 'pawapay'
                ORDER BY created_at ASC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':minutes_old', $minutesOld, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("Failed to get transactions for reconciliation: " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique transaction ID compatible with Modesy format
     * 
     * @return string Unique transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'PWP_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
    }
    
    /**
     * Get database connection for direct queries if needed
     * 
     * @return PDO Database connection
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    /**
     * Begin database transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit database transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback database transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }
    
    /**
     * Check if PawaPay database schema is properly installed
     * 
     * @return array Status array with check results
     */
    public function validateSchema(): array
    {
        $status = [
            'valid' => true,
            'tables' => [],
            'errors' => []
        ];
        
        try {
            // Check required tables
            $requiredTables = ['payment_gateways', 'transactions', 'webhook_logs', 'orders'];
            
            foreach ($requiredTables as $table) {
                $tableName = $this->getTableName($table);
                
                $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$tableName]);
                
                if ($stmt->fetch()) {
                    $status['tables'][$table] = 'exists';
                } else {
                    $status['tables'][$table] = 'missing';
                    $status['valid'] = false;
                    $status['errors'][] = "Table {$tableName} is missing";
                }
            }
            
            // Check PawaPay gateway configuration
            $gateway = $this->getPaymentGatewayConfig('pawapay');
            if (!$gateway) {
                $status['valid'] = false;
                $status['errors'][] = "PawaPay gateway configuration not found";
            }
            
        } catch (Exception $e) {
            $status['valid'] = false;
            $status['errors'][] = "Schema validation failed: " . $e->getMessage();
        }
        
        return $status;
    }
}