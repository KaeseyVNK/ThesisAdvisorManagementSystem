<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.php">
                    <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (!isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/index.php">Trang chủ</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/login.php">Đăng nhập</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/dashboard.php">Bảng điều khiển</a>
                            </li>
                            <?php if (isAdmin()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Quản lý
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/students.php">Sinh viên</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/lecturers.php">Giảng viên</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/theses.php">Đề tài</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/assignments.php">Phân công</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports.php">Báo cáo</a></li>
                                    </ul>
                                </li>
                            <?php elseif (isFaculty()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="facultyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Giảng viên
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="facultyDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/students.php">Sinh viên hướng dẫn</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/thesis.php">Đề tài</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/progress.php">Tiến độ</a></li>
                                    </ul>
                                </li>
                            <?php elseif (isStudent()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Sinh viên
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="studentDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/advisor.php">Giảng viên hướng dẫn</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/thesis.php">Đề tài</a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/progress.php">Tiến độ</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">Hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/change-password.php">Đổi mật khẩu</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Đăng xuất</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php displayFlashMessage(); ?>
        <!-- The main content will be inserted here -->
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            new bootstrap.Dropdown(dropdown);
        });
    });
    </script>
</body>
</html> 