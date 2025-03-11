<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('Vui lòng đăng nhập để tiếp tục', 'warning');
    redirect('login.php');
}

// Get user role
$role = getCurrentUserRole();
$userDetails = getCurrentUserDetails();

// Include header
$pageTitle = 'Bảng điều khiển';
include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Bảng điều khiển</h1>

    <div class="row">
        <?php if (isAdmin()): ?>
            <!-- Admin Cards -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user-graduate me-2"></i>Sinh viên
                        </h5>
                        <p class="card-text">Quản lý thông tin sinh viên trong hệ thống.</p>
                        <a href="admin/students.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên
                        </h5>
                        <p class="card-text">Quản lý thông tin giảng viên trong hệ thống.</p>
                        <a href="admin/lecturers.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-book me-2"></i>Đề tài
                        </h5>
                        <p class="card-text">Quản lý thông tin đề tài luận văn.</p>
                        <a href="admin/theses.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tasks me-2"></i>Phân công
                        </h5>
                        <p class="card-text">Phân công giảng viên hướng dẫn đề tài.</p>
                        <a href="admin/assignments.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

        <?php elseif (isFaculty()): ?>
            <!-- Faculty Cards -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user-graduate me-2"></i>Sinh viên hướng dẫn
                        </h5>
                        <p class="card-text">Xem danh sách sinh viên đang hướng dẫn.</p>
                        <a href="faculty/students.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-book me-2"></i>Đề tài
                        </h5>
                        <p class="card-text">Quản lý đề tài đang hướng dẫn.</p>
                        <a href="faculty/theses.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tasks me-2"></i>Phân công
                        </h5>
                        <p class="card-text">Phân công sinh viên vào đề tài.</p>
                        <a href="faculty/assignments.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Student Cards -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên hướng dẫn
                        </h5>
                        <p class="card-text">Xem thông tin giảng viên hướng dẫn.</p>
                        <a href="student/advisor.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-book me-2"></i>Đề tài
                        </h5>
                        <p class="card-text">Xem thông tin đề tài đang thực hiện.</p>
                        <a href="student/thesis.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 