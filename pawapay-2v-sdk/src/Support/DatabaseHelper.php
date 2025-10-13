<?php
/**
 * PawaPay Database Helper - Myzuwa Payment Gateway Integration
 *
 * @package     Myzuwa\PawaPay\Support
 * @version     1.0.0
 * @author      AI Assistant - September 2025
 *
 * @link        ../Docs/integration_guide.md - Integration Guide
 * @link        ../Docs/pawapay_documentation.md - PawaPay API Docs
 * @link        ../PRODUCTION_READINESS_CHECKLIST.md - Production Checklist
 * @link        ../XAMPP_TEST_RESULTS.md - Testing Results
 *
 * FUNCTIONAL OVERVIEW:
 * This utility provides standardized PDO database access for PawaPay transactions.
 * Handles Orders, Memberships, Payments, and Vendor Payout processing.
 *
 * AI ADOPTION INSTRUCTIONS:
 * ──────────────────────────────────────────────────────────────────────
 * 1. ALWAYS use this helper instead of raw PDO or ConfigDatabase calls
 * 2. For new queries: Use fetch() for SELECT, execute() for INSERT/UPDATE
 * 3. Error handling: All methods throw exceptions - always wrap in try/catch
 * 4. Transactions: Use getConnection() for multi-statement operations
 * 5. Security: All queries use prepared statements - NO raw SQL interpolation
 *
 * USAGE EXAMPLES:
 * ──────────────────────────────────────────────────────────────────────
 * // Get user by ID
 * $user = DatabaseHelper::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
 *
 * // Insert payment record
 * $id = DatabaseHelper::insert('payments', ['amount' => 100.00, 'currency' => 'ZMW']);
 *
 * // Update order status
 * DatabaseHelper::update('orders', ['status' => 'completed'], ['id' => $orderId]);
 *
 * DEPENDENCIES:
 * - PDO extension (PHP 7.4+)
 * - ConfigDatabase class from Modesy framework
 * - Environment variables: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *
 * DATABASE TABLES USED:
 * - orders: Order status and payment tracking
 * - order_transactions: Transaction audit trail
 * - pending_payments: Payment processing state
 * - membership_payments: Membership activation
 * - vendor_earnings: Vendor payout calculations
 * - vendor_payouts: Payout transaction records
 */

namespace Myzuwa\PawaPay\Support;

/**
 * Simple Database Helper for PawaPay Integration
 *
 * Provides direct PDO database access for PawaPay operations
 * Designed for clean separation between PawaPay logic and Modesy core
 *
 * @link ../app/Controllers/CartController.php - Uses this helper extensively
 * @link ../app/Controllers/Admin/PayoutController.php - Uses for payout management
 * @link ../database/migrations/003_create_vendor_payouts_table.php - Database schema
 */
class DatabaseHelper
{
    /** @var \PDO */
    private static $pdo;

    /**
     * Get PDO database connection
     */
    public static function getConnection(): \PDO
    {
        if (!self::$pdo) {
            // Use Modesy's database configuration
            if (function_exists('ConfigDatabase::connect')) {
                $config = \ConfigDatabase::connect();

                // If it returns PDO, use it directly
                if ($config instanceof \PDO) {
                    self::$pdo = $config;
                }
                // Otherwise, try to connect directly to MySQL
                else {
                    $dbConfig = [
                        'host' => $_ENV['DB_HOST'] ?? 'localhost',
                        'database' => $_ENV['DB_DATABASE'] ?? 'myzuwa',
                        'username' => $_ENV['DB_USERNAME'] ?? 'root',
                        'password' => $_ENV['DB_PASSWORD'] ?? '',
                        'charset' => 'utf8mb4'
                    ];

                    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
                    self::$pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                    ]);
                }
            }
            // Fall back to environment variables
            else {
                $dbConfig = [
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'database' => $_ENV['DB_DATABASE'] ?? 'myzuwa',
                    'username' => $_ENV['DB_USERNAME'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8mb4'
                ];

                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
                self::$pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]);
            }
        }

        return self::$pdo;
    }

    /**
     * Execute query and return result
     */
    public static function query(string $sql, array $params = [], bool $fetchObject = false)
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);

        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();

        return $fetchObject ? $stmt->fetchObject() : $stmt->fetchAll();
    }

    /**
     * Execute insert/update/delete query and return affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);

        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Get a single row
     */
    public static function fetch(string $sql, array $params = [], bool $fetchObject = false)
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);

        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();

        return $fetchObject ? $stmt->fetchObject() : $stmt->fetch();
    }

    /**
     * Insert record and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";

        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public static function update(string $table, array $data, array $where): int
    {
        $setColumns = [];
        $whereColumns = [];
        $params = array_values($data);

        foreach (array_keys($data) as $column) {
            $setColumns[] = "{$column} = ?";
        }

        foreach ($where as $column => $value) {
            $whereColumns[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(',', $setColumns) .
               " WHERE " . implode(' AND ', $whereColumns);

        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
