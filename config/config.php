<?php
/**
 * Database Configuration
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'thesismanagementdb');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password

/**
 * Application Configuration
 */
define('APP_NAME', 'Thesis Advisor Management System');
define('APP_URL', 'http://localhost/BTNhom_1103');  // Change this to your application URL
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/uploads');

/**
 * Error Reporting
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Session Configuration
 */
session_start();

/**
 * Database Connection
 */
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $conn;
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
} 