<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize user input
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to a specific page
 * 
 * @param string $location URL to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: " . $location);
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is faculty
 * 
 * @return bool True if user is faculty, false otherwise
 */
function isFaculty() {
    return hasRole('faculty');
}

/**
 * Check if user is student
 * 
 * @return bool True if user is student, false otherwise
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 * 
 * @return string|null User role if logged in, null otherwise
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Display flash message
 * 
 * @return void
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Set flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get user details by ID
 * 
 * @param int $userId User ID
 * @return array|false User details or false if not found
 */
function getUserById($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get student details by user ID
 * 
 * @param int $userId User ID
 * @return array|false Student details or false if not found
 */
function getStudentByUserId($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE UserID = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get faculty details by user ID
 * 
 * @param int $userId User ID
 * @return array|false Faculty details or false if not found
 */
function getFacultyByUserId($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE UserID = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get current user details based on role
 * 
 * @return array|false User details or false if not found
 */
function getCurrentUserDetails() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = getCurrentUserId();
    $role = getCurrentUserRole();
    
    if ($role === 'student') {
        return getStudentByUserId($userId);
    } elseif ($role === 'faculty') {
        return getFacultyByUserId($userId);
    } elseif ($role === 'admin') {
        return getUserById($userId);
    }
    
    return false;
} 