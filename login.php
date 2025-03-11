<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username)) {
        $errors[] = 'Tên đăng nhập không được để trống';
    }
    
    if (empty($password)) {
        $errors[] = 'Mật khẩu không được để trống';
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT * FROM Users WHERE Username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['Password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Role'];
                
                setFlashMessage('Đăng nhập thành công!', 'success');
                redirect('dashboard.php');
            } else {
                $errors[] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Include header
$pageTitle = 'Đăng nhập';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="auth-form">
            <div class="form-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2 class="form-title">Đăng nhập</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    <div class="invalid-feedback">Vui lòng nhập tên đăng nhập</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="input-group-text">
                            <i class="fas fa-eye toggle-password" toggle="#password"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback">Vui lòng nhập mật khẩu</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Đăng nhập</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
                <p><a href="forgot-password.php">Quên mật khẩu?</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 