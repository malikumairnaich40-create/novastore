<?php
/**
 * Database Connection Handler (PDO Singleton)
 * 
 * Provides a secure, persistent PDO connection with prepared statements,
 * error exception mode, and UTF-8 collation.
 */

class Database {
    private static ?PDO $instance = null;

    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'ecommerce_db';
    private const DB_PORT = '3306';
    private const DB_CHARSET = 'utf8mb4';

    /**
     * Get the PDO Database Instance
     * 
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Enforce true prepared statements for SQL injection security
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, provide helpful message
                $errMsg = "Database Connection Failed: " . $e->getMessage();
                error_log($errMsg);
                die("<div style='font-family: sans-serif; padding: 2rem; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 8px; max-width: 600px; margin: 40px auto; color: #9b2c2c;'>
                        <h3 style='margin-top: 0;'>⚠️ Database Connection Error</h3>
                        <p>{$errMsg}</p>
                        <p style='font-size: 14px; color: #4a5568;'>Make sure MySQL is running in XAMPP and you have imported <code>database.sql</code> into phpMyAdmin.</p>
                     </div>");
            }
        }

        return self::$instance;
    }

    // Prevent direct instantiation & cloning
    private function __construct() {}
    private function __clone() {}
}
