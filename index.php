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

<div class="container py-5">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">Hệ thống Quản lý Hướng dẫn Luận văn</h1>
            <p class="lead mb-4">Nền tảng hiện đại giúp sinh viên và giảng viên theo dõi quá trình hướng dẫn luận văn một cách hiệu quả.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <a href="login.php" class="btn btn-primary btn-lg px-4 me-md-2">Đăng nhập</a>
                <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">Đăng ký</a>
            </div>
        </div>
        <div class="col-lg-6">
            <img src="assets/images/thesis.svg" alt="Thesis Illustration" class="img-fluid">
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Tính năng chính</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h3 class="card-title">Quản lý Sinh viên & Giảng viên</h3>
                        <p class="card-text">Dễ dàng quản lý thông tin sinh viên và giảng viên hướng dẫn trong hệ thống.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                        <h3 class="card-title">Theo dõi Tiến độ</h3>
                        <p class="card-text">Cập nhật và theo dõi tiến độ thực hiện luận văn một cách trực quan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                        <h3 class="card-title">Báo cáo & Thống kê</h3>
                        <p class="card-text">Tạo báo cáo và thống kê chi tiết về quá trình hướng dẫn luận văn.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <h2 class="text-center mb-5">Đối tượng sử dụng</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title"><i class="fas fa-user-graduate text-primary me-2"></i>Sinh viên</h3>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Đăng ký đề tài luận văn</li>
                        <li class="list-group-item">Xem thông tin giảng viên hướng dẫn</li>
                        <li class="list-group-item">Cập nhật tiến độ thực hiện</li>
                        <li class="list-group-item">Nhận phản hồi từ giảng viên</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title"><i class="fas fa-chalkboard-teacher text-primary me-2"></i>Giảng viên</h3>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Quản lý sinh viên hướng dẫn</li>
                        <li class="list-group-item">Duyệt đề tài luận văn</li>
                        <li class="list-group-item">Theo dõi tiến độ sinh viên</li>
                        <li class="list-group-item">Cung cấp phản hồi và đánh giá</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title"><i class="fas fa-user-shield text-primary me-2"></i>Quản trị viên</h3>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Quản lý tài khoản người dùng</li>
                        <li class="list-group-item">Phân công giảng viên hướng dẫn</li>
                        <li class="list-group-item">Giám sát quá trình hướng dẫn</li>
                        <li class="list-group-item">Tạo báo cáo thống kê</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 