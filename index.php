<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect to dashboard if logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Include header
$pageTitle = 'Trang chủ';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Hệ thống Quản lý Hướng dẫn Luận văn</h1>
                <p class="lead mb-4">Nền tảng hiện đại giúp sinh viên và giảng viên theo dõi quá trình hướng dẫn luận văn một cách hiệu quả, chuyên nghiệp.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a href="login.php" class="btn btn-primary btn-lg px-4 me-md-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Đăng ký
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/images/thesis.svg" alt="Thesis Illustration" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Tính năng nổi bật</h2>
            <p class="text-muted">Hệ thống cung cấp đầy đủ các tính năng cần thiết cho quá trình quản lý hướng dẫn luận văn</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white mb-4">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Quản lý Sinh viên & Giảng viên</h3>
                        <p class="card-text">Dễ dàng quản lý thông tin sinh viên và giảng viên hướng dẫn trong hệ thống với giao diện trực quan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white mb-4">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Theo dõi Tiến độ</h3>
                        <p class="card-text">Cập nhật và theo dõi tiến độ thực hiện luận văn một cách trực quan, giúp giảng viên nắm bắt tình hình sinh viên.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-gradient text-white mb-4">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Báo cáo & Thống kê</h3>
                        <p class="card-text">Tạo báo cáo và thống kê chi tiết về quá trình hướng dẫn luận văn, giúp đánh giá hiệu quả công việc.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Cách thức hoạt động</h2>
            <p class="text-muted">Quy trình đơn giản, hiệu quả cho việc quản lý hướng dẫn luận văn</p>
        </div>
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-number">1</div>
                        <div class="timeline-content">
                            <h4>Đăng ký tài khoản</h4>
                            <p>Sinh viên và giảng viên đăng ký tài khoản trên hệ thống với thông tin cá nhân và học thuật.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">2</div>
                        <div class="timeline-content">
                            <h4>Phân công hướng dẫn</h4>
                            <p>Quản trị viên phân công giảng viên hướng dẫn cho từng đề tài, sau đó giảng viên phân công sinh viên.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">3</div>
                        <div class="timeline-content">
                            <h4>Cập nhật tiến độ</h4>
                            <p>Sinh viên thường xuyên cập nhật tiến độ thực hiện luận văn lên hệ thống để giảng viên theo dõi.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">4</div>
                        <div class="timeline-content">
                            <h4>Phản hồi và đánh giá</h4>
                            <p>Giảng viên cung cấp phản hồi, đánh giá và hướng dẫn cho sinh viên trong quá trình thực hiện.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">5</div>
                        <div class="timeline-content">
                            <h4>Hoàn thành luận văn</h4>
                            <p>Sinh viên hoàn thành luận văn và giảng viên xác nhận hoàn thành trên hệ thống.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- User Roles Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Đối tượng sử dụng</h2>
            <p class="text-muted">Hệ thống được thiết kế cho 3 đối tượng người dùng chính</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title h5 mb-3">
                            <i class="fas fa-user-graduate text-primary me-2"></i>Sinh viên
                        </h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Đăng ký đề tài luận văn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Xem thông tin giảng viên hướng dẫn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Cập nhật tiến độ thực hiện
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Nhận phản hồi từ giảng viên
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title h5 mb-3">
                            <i class="fas fa-chalkboard-teacher text-primary me-2"></i>Giảng viên
                        </h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Quản lý sinh viên hướng dẫn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Duyệt đề tài luận văn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Theo dõi tiến độ sinh viên
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Cung cấp phản hồi và đánh giá
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title h5 mb-3">
                            <i class="fas fa-user-shield text-primary me-2"></i>Quản trị viên
                        </h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Quản lý tài khoản người dùng
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Phân công giảng viên hướng dẫn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Giám sát quá trình hướng dẫn
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-check-circle text-success me-2"></i>Tạo báo cáo thống kê
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-4">Bắt đầu sử dụng ngay hôm nay</h2>
                <p class="lead mb-4">Đăng ký tài khoản để trải nghiệm hệ thống quản lý hướng dẫn luận văn hiện đại và hiệu quả.</p>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="login.php" class="btn btn-light btn-lg px-4 me-sm-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Đăng ký
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.hero-section {
    padding: 5rem 0;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.feature-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto;
}

.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 2px;
    height: 100%;
    background: var(--secondary-color);
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
}

.timeline-number {
    width: 50px;
    height: 50px;
    background-color: var(--secondary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
    z-index: 1;
    flex-shrink: 0;
}

.timeline-content {
    background-color: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-left: 1.5rem;
    flex-grow: 1;
}

.timeline-content h4 {
    margin-bottom: 0.5rem;
    color: var(--primary-color);
    font-weight: 600;
}

.timeline-content p {
    margin-bottom: 0;
    color: var(--gray-color);
}
</style>

<?php include 'includes/footer.php'; ?> 