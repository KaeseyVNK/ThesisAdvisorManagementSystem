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

// Set a page flag to remove header
$hideHeader = true;
$pageTitle = 'Đăng nhập';
// Include header
include 'includes/header.php';
?>

<div class="modern-login-container">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>
    
    <div class="container position-relative">
        <div class="row">
            <div class="col-lg-6 d-none d-lg-flex align-items-center">
                <div class="login-illustration">
                    <svg class="animated-svg" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#3498db;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#2c3e50;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <path d="M100,250 C100,150 200,50 300,150 C400,250 350,400 250,400 C150,400 100,350 100,250 Z" fill="url(#grad1)" class="blob-path" />
                        <path d="M115,230 L120,220 L250,220 L250,230 Z" stroke="#fff" stroke-width="4" class="line-path" />
                        <path d="M115,250 L120,240 L270,240 L270,250 Z" stroke="#fff" stroke-width="4" class="line-path" />
                        <path d="M115,270 L120,260 L240,260 L240,270 Z" stroke="#fff" stroke-width="4" class="line-path" />
                        <circle cx="340" cy="170" r="15" fill="#fff" class="circle-path" />
                    </svg>
                    <h2 class="illustration-text mb-4">Quản lý luận văn thông minh</h2>
                    <p class="illustration-subtext">Nền tảng hiện đại giúp sinh viên và giảng viên quản lý quá trình hướng dẫn luận văn hiệu quả.</p>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-8 col-sm-10 mx-auto">
                <div class="modern-login-card">
                    <div class="login-header text-center mb-5">
                        <a href="index.php" class="d-block mb-4">
                            <div class="modern-login-logo">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        </a>
                        <h2 class="modern-login-title">Chào mừng trở lại!</h2>
                        <p class="modern-login-subtitle">Đăng nhập để tiếp tục hành trình của bạn</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-glass mb-4" role="alert">
                            <div class="d-flex">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="alert-heading mb-1">Không thể đăng nhập</h6>
                                    <p class="mb-0"><?php echo $errors[0]; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="modern-login-form">
                        <div class="form-floating floating-label mb-4">
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   placeholder=" " required>
                            <label for="username">
                                <i class="fas fa-user me-2"></i>Tên đăng nhập
                            </label>
                        </div>
                        
                        <div class="form-floating floating-label mb-4">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder=" " required>
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>Mật khẩu
                            </label>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                            </div>
                            <a href="forgot-password.php" class="forgot-link">Quên mật khẩu?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-glow w-100 mb-4">
                            <span>Đăng nhập</span>
                            <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <div class="text-center mb-4">
                            <p class="mb-0">Chưa có tài khoản? <a href="register.php" class="register-link">Đăng ký ngay</a></p>
                        </div>
                    </form>
                    
                    <div class="divider-text"><span>hoặc đăng nhập với</span></div>
                    
                    <div class="social-login-buttons">
                        <button class="btn social-btn google-btn">
                            <i class="fab fa-google"></i>
                        </button>
                        <button class="btn social-btn facebook-btn">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="btn social-btn github-btn">
                            <i class="fab fa-github"></i>
                        </button>
                    </div>
                    
                    <div class="back-home text-center mt-4">
                        <a href="index.php" class="back-link">
                            <i class="fas fa-home me-1"></i>Quay lại trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --accent-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --card-bg: rgba(255, 255, 255, 0.9);
    --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
    --input-bg: rgba(255, 255, 255, 0.8);
    --input-border: rgba(106, 17, 203, 0.2);
    --input-focus: rgba(106, 17, 203, 0.5);
    --button-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
}

.modern-login-container {
    min-height: 100vh;
    padding: 80px 0;
    background-color: #f8faff;
    position: relative;
    overflow: hidden;
}

.shape {
    position: absolute;
    border-radius: 50%;
    background: var(--primary-gradient);
    animation: float 8s ease-in-out infinite;
    opacity: 0.6;
    z-index: 0;
}

.shape-1 {
    width: 300px;
    height: 300px;
    top: -150px;
    left: -100px;
    filter: blur(30px);
    animation-delay: 0s;
}

.shape-2 {
    width: 200px;
    height: 200px;
    bottom: -100px;
    right: -50px;
    filter: blur(20px);
    background: var(--secondary-gradient);
    animation-delay: 2s;
}

.shape-3 {
    width: 150px;
    height: 150px;
    bottom: 20%;
    left: 10%;
    filter: blur(15px);
    background: var(--accent-gradient);
    animation-delay: 4s;
}

.shape-4 {
    width: 100px;
    height: 100px;
    top: 30%;
    right: 10%;
    filter: blur(10px);
    background: var(--secondary-gradient);
    animation-delay: 6s;
}

@keyframes float {
    0% {
        transform: translateY(0px) scale(1);
    }
    50% {
        transform: translateY(-20px) scale(1.05);
    }
    100% {
        transform: translateY(0px) scale(1);
    }
}

.modern-login-card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: var(--card-shadow);
    position: relative;
    z-index: 10;
    transform: translateY(0);
    transition: all 0.5s ease;
    animation: fadeInUp 1s forwards;
}

.modern-login-logo {
    width: 80px;
    height: 80px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 2rem;
    box-shadow: 0 10px 20px rgba(106, 17, 203, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(106, 17, 203, 0.4);
    }
    70% {
        box-shadow: 0 0 0 15px rgba(106, 17, 203, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(106, 17, 203, 0);
    }
}

.modern-login-title {
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: #333;
    background: linear-gradient(to right, #6a11cb, #2575fc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.modern-login-subtitle {
    color: #555;
    margin-bottom: 0;
}

.floating-label {
    position: relative;
}

.form-floating > .form-control {
    background-color: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 12px;
    height: 60px;
    padding: 1rem 1rem 0.5rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-floating > .form-control:focus {
    border-color: var(--input-focus);
    box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
}

.form-floating > label {
    padding: 1rem 1rem;
    color: #555;
    font-weight: 500;
    transition: all 0.3s ease;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    cursor: pointer;
    color: #555;
    transition: all 0.3s ease;
}

.password-toggle:hover {
    color: #6a11cb;
}

.form-check-input {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 2px solid var(--input-border);
}

.form-check-input:checked {
    background-color: #6a11cb;
    border-color: #6a11cb;
}

.form-check-label {
    color: #555;
    font-weight: 500;
    padding-left: 0.25rem;
}

.forgot-link {
    color: #6a11cb;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.forgot-link:hover {
    color: #2575fc;
    text-decoration: underline;
}

.btn-glow {
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    padding: 14px 20px;
    position: relative;
    z-index: 1;
    overflow: hidden;
    transition: all 0.5s ease;
    box-shadow: var(--button-shadow);
}

.btn-glow:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--secondary-gradient);
    z-index: -1;
    transition: all 0.5s ease;
    opacity: 0;
}

.btn-glow:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(106, 17, 203, 0.5);
    color: white;
}

.btn-glow:hover:before {
    opacity: 1;
}

.btn-glow span {
    z-index: 2;
    position: relative;
}

.register-link {
    color: #6a11cb;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.register-link:hover {
    color: #2575fc;
    text-decoration: underline;
}

.divider-text {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 30px 0;
    color: #777;
}

.divider-text:before,
.divider-text:after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #ddd;
}

.divider-text span {
    padding: 0 15px;
    font-size: 0.9rem;
}

.social-login-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 20px;
}

.social-btn {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    transition: all 0.3s ease;
    border: none;
}

.google-btn {
    background: linear-gradient(135deg, #EA4335 0%, #FF7676 100%);
    box-shadow: 0 5px 15px rgba(234, 67, 53, 0.3);
}

.facebook-btn {
    background: linear-gradient(135deg, #3b5998 0%, #4a6fba 100%);
    box-shadow: 0 5px 15px rgba(59, 89, 152, 0.3);
}

.github-btn {
    background: linear-gradient(135deg, #333 0%, #555 100%);
    box-shadow: 0 5px 15px rgba(51, 51, 51, 0.3);
}

.social-btn:hover {
    transform: translateY(-3px);
    color: white;
}

.back-link {
    color: #555;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: #6a11cb;
}

/* Alert styling */
.alert-glass {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border-left: 4px solid #f5576c;
    border-radius: 10px;
    padding: 15px;
}

.alert-icon {
    font-size: 1.5rem;
    color: #f5576c;
}

.alert-heading {
    color: #f5576c;
    font-weight: 600;
}

/* Login illustration */
.login-illustration {
    padding: 2rem;
    text-align: center;
}

.animated-svg {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
}

.blob-path {
    animation: morphBlob 10s infinite alternate;
}

.line-path {
    stroke-dasharray: 200;
    stroke-dashoffset: 200;
    animation: dashAnimation 3s forwards infinite alternate;
}

.circle-path {
    animation: bounce 2s infinite alternate;
}

@keyframes morphBlob {
    0% {
        d: path("M100,250 C100,150 200,50 300,150 C400,250 350,400 250,400 C150,400 100,350 100,250 Z");
    }
    50% {
        d: path("M130,220 C130,150 230,100 300,180 C370,260 320,380 220,380 C120,380 130,300 130,220 Z");
    }
    100% {
        d: path("M100,250 C100,150 200,50 300,150 C400,250 350,400 250,400 C150,400 100,350 100,250 Z");
    }
}

@keyframes dashAnimation {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes bounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-15px);
    }
}

.illustration-text {
    color: #333;
    font-weight: 700;
    font-size: 1.8rem;
    opacity: 0;
    animation: fadeIn 1s forwards 0.5s;
}

.illustration-subtext {
    color: #555;
    opacity: 0;
    animation: fadeIn 1s forwards 1s;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@media (max-width: 992px) {
    .modern-login-card {
        padding: 30px 20px;
        margin-top: 50px;
    }
}
</style>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            let x = e.clientX - e.target.offsetLeft;
            let y = e.clientY - e.target.offsetTop;
            
            let ripple = document.createElement('span');
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 