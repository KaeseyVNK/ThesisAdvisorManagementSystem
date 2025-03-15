<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php if (!isset($hideHeader)): ?>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
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
                                <a class="nav-link" href="<?php echo APP_URL; ?>/index.php">
                                    <i class="fas fa-home me-1"></i>Trang chủ
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-outline-light btn-sm ms-2 px-3" href="<?php echo APP_URL; ?>/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Bảng điều khiển
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs me-1"></i>Quản lý
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/students.php">
                                            <i class="fas fa-user-graduate me-2"></i>Sinh viên
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/lecturers.php">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/theses.php">
                                            <i class="fas fa-book me-2"></i>Đề tài
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/assignments.php">
                                            <i class="fas fa-tasks me-2"></i>Phân công
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports.php">
                                            <i class="fas fa-chart-bar me-2"></i>Báo cáo
                                        </a></li>
                                    </ul>
                                </li>
                            <?php elseif (isFaculty()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="facultyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>Giảng viên
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="facultyDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/students.php">
                                            <i class="fas fa-user-graduate me-2"></i>Sinh viên hướng dẫn
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/theses.php">
                                            <i class="fas fa-book me-2"></i>Đề tài
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/assignments.php">
                                            <i class="fas fa-tasks me-2"></i>Phân công
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/faculty/progress.php">
                                            <i class="fas fa-chart-line me-2"></i>Tiến độ
                                        </a></li>
                                    </ul>
                                </li>
                            <?php elseif (isStudent()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user-graduate me-1"></i>Sinh viên
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="studentDropdown">
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/advisor.php">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên hướng dẫn
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/thesis.php">
                                            <i class="fas fa-book me-2"></i>Đề tài
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/student/progress.php">
                                            <i class="fas fa-tasks me-2"></i>Tiến độ
                                        </a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">
                                        <i class="fas fa-id-card me-2"></i>Hồ sơ
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/change-password.php">
                                        <i class="fas fa-key me-2"></i>Đổi mật khẩu
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item logout-item" href="<?php echo APP_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <?php endif; ?>
    
    <?php if (!isset($hideHeader)): ?>
    <main class="container py-4">
        <?php displayFlashMessage(); ?>
        <!-- The main content will be inserted here -->
    <?php else: ?>
    <main>
        <?php displayFlashMessage(); ?>
        <!-- The main content without container will be inserted here -->
    <?php endif; ?>

<?php if (!isset($hideHeader)): ?>
<style>
/* Navbar styling improvements */
.navbar {
    padding: 1rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.5px;
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.navbar-dark .navbar-nav .nav-item {
    margin-left: 5px;
}

.btn-outline-light {
    border-width: 2px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.dropdown-menu {
    border: none;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 0.5rem;
    margin-top: 10px;
}

.dropdown-item {
    padding: 0.6rem 1rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: var(--secondary-color);
    color: white;
}

.user-dropdown {
    display: flex;
    align-items: center;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 0.5rem 1rem;
}

.user-dropdown:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.logout-item {
    color: var(--danger-color);
}

.logout-item:hover {
    background-color: var(--danger-color);
    color: white;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .navbar-collapse {
        background-color: var(--primary-color);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-dark .navbar-nav .nav-item {
        margin-left: 0;
        margin-bottom: 5px;
    }
    
    .user-dropdown {
        margin-top: 10px;
    }
}
</style>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    if (dropdowns.length > 0) {
        dropdowns.forEach(function(dropdown) {
            new bootstrap.Dropdown(dropdown);
        });
    }
    
    // Add active class to current nav item
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (linkPath && currentLocation.includes(linkPath) && linkPath !== '<?php echo APP_URL; ?>/index.php') {
            link.classList.add('active');
            
            // If it's in a dropdown, also highlight the dropdown toggle
            const parentDropdown = link.closest('.dropdown');
            if (parentDropdown) {
                const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    dropdownToggle.classList.add('active');
                }
            }
        }
    });
});
</script>
</body>
</html> 