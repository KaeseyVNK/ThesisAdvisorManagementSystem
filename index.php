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

<!-- Hero Section with Animated Elements -->
<section class="hero-section position-relative overflow-hidden">
    <div class="container position-relative z-3">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content" data-aos="fade-right">
                <h1 class="display-4 fw-bold mb-4 text-gradient">Hệ thống Quản lý Hướng dẫn Luận văn</h1>
                <p class="lead mb-4">Nền tảng hiện đại giúp sinh viên và giảng viên theo dõi quá trình hướng dẫn luận văn một cách hiệu quả, chuyên nghiệp.</p>
                <div class="d-grid gap-3 d-md-flex justify-content-md-start">
                    <a href="login.php" class="btn btn-primary btn-lg px-4 me-md-2 pulse-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-delay="200">
                <div class="hero-image-container">
                    <img src="assets/images/thesisP.jpg" alt="Thesis Illustration" class="img-fluid floating">
                </div>
            </div>
        </div>
    </div>
    <!-- Animated background elements -->
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>
</section>

<!-- Features Section with Cards -->
<section class="py-5 features-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-primary px-3 py-2 mb-2">Tính năng</span>
            <h2 class="fw-bold section-title">Tính năng nổi bật</h2>
            <p class="text-muted section-subtitle">Hệ thống cung cấp đầy đủ các tính năng cần thiết cho quá trình quản lý hướng dẫn luận văn</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-hover feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white mb-4">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Quản lý Sinh viên & Giảng viên</h3>
                        <p class="card-text">Dễ dàng quản lý thông tin sinh viên và giảng viên hướng dẫn trong hệ thống với giao diện trực quan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-hover feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white mb-4">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Theo dõi Tiến độ</h3>
                        <p class="card-text">Cập nhật và theo dõi tiến độ thực hiện luận văn một cách trực quan, giúp giảng viên nắm bắt tình hình sinh viên.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 border-0 shadow-hover feature-card">
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

<!-- How It Works Section with Modern Timeline -->
<section class="py-5 bg-light how-it-works-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-secondary px-3 py-2 mb-2">Quy trình</span>
            <h2 class="fw-bold section-title">Cách thức hoạt động</h2>
            <p class="text-muted section-subtitle">Quy trình đơn giản, hiệu quả cho việc quản lý hướng dẫn luận văn</p>
        </div>
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="timeline">
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="timeline-number">1</div>
                        <div class="timeline-content">
                            <h4>Đăng nhập hệ thống</h4>
                            <p>Sinh viên và giảng viên đăng nhập vào hệ thống với tài khoản được cấp.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="timeline-number">2</div>
                        <div class="timeline-content">
                            <h4>Phân công hướng dẫn</h4>
                            <p>Quản trị viên phân công giảng viên hướng dẫn cho từng đề tài, sau đó giảng viên phân công sinh viên.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="timeline-number">3</div>
                        <div class="timeline-content">
                            <h4>Cập nhật tiến độ</h4>
                            <p>Sinh viên thường xuyên cập nhật tiến độ thực hiện luận văn lên hệ thống để giảng viên theo dõi.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                        <div class="timeline-number">4</div>
                        <div class="timeline-content">
                            <h4>Phản hồi và đánh giá</h4>
                            <p>Giảng viên cung cấp phản hồi, đánh giá và hướng dẫn cho sinh viên trong quá trình thực hiện.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="500">
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

<!-- User Roles Section with Hover Effects -->
<section class="py-5 user-roles-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-info px-3 py-2 mb-2">Đối tượng</span>
            <h2 class="fw-bold section-title">Đối tượng sử dụng</h2>
            <p class="text-muted section-subtitle">Hệ thống được thiết kế cho 3 đối tượng người dùng chính</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-hover role-card">
                    <div class="card-body p-4">
                        <div class="role-icon mb-3">
                            <i class="fas fa-user-graduate text-primary"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Sinh viên</h3>
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
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-hover role-card">
                    <div class="card-body p-4">
                        <div class="role-icon mb-3">
                            <i class="fas fa-chalkboard-teacher text-primary"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Giảng viên</h3>
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
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 border-0 shadow-hover role-card">
                    <div class="card-body p-4">
                        <div class="role-icon mb-3">
                            <i class="fas fa-user-shield text-primary"></i>
                        </div>
                        <h3 class="card-title h5 mb-3">Quản trị viên</h3>
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

<!-- Call to Action Section with Gradient Background -->
<section class="py-5 cta-section text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center" data-aos="zoom-in">
                <h2 class="fw-bold mb-4">Bắt đầu sử dụng ngay hôm nay</h2>
                <p class="lead mb-4">Hệ thống quản lý hướng dẫn luận văn hiện đại và hiệu quả dành cho nội bộ.</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="login.php" class="btn btn-light btn-lg px-4 pulse-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập hệ thống
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Enhanced Hero Section */
.hero-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
    color: white;
    overflow: hidden;
    position: relative;
}

.text-gradient {
    background: linear-gradient(to right, #fff, #74b9ff);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-image-container {
    position: relative;
    z-index: 2;
}

.floating {
    animation: floating 4s ease-in-out infinite;
}

@keyframes floating {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(2deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

/* Hero background shapes */
.hero-shapes {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.shape {
    position: absolute;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.shape-1 {
    width: 400px;
    height: 400px;
    top: -150px;
    right: -50px;
    animation: move1 20s linear infinite;
    background: radial-gradient(circle at center, rgba(41, 128, 185, 0.1) 0%, rgba(41, 128, 185, 0) 70%);
}

.shape-2 {
    width: 300px;
    height: 300px;
    bottom: -100px;
    left: 10%;
    animation: move2 25s linear infinite;
    background: radial-gradient(circle at center, rgba(52, 152, 219, 0.1) 0%, rgba(52, 152, 219, 0) 70%);
}

.shape-3 {
    width: 200px;
    height: 200px;
    bottom: 20%;
    right: 10%;
    animation: move3 30s linear infinite;
    background: radial-gradient(circle at center, rgba(155, 89, 182, 0.1) 0%, rgba(155, 89, 182, 0) 70%);
}

.shape-4 {
    width: 150px;
    height: 150px;
    top: 30%;
    left: 20%;
    animation: move4 35s linear infinite;
    background: radial-gradient(circle at center, rgba(46, 204, 113, 0.1) 0%, rgba(46, 204, 113, 0) 70%);
}

@keyframes move1 {
    0% { transform: rotate(0deg) translateX(50px) rotate(0deg) scale(1); }
    50% { transform: rotate(180deg) translateX(50px) rotate(-180deg) scale(1.2); }
    100% { transform: rotate(360deg) translateX(50px) rotate(-360deg) scale(1); }
}

@keyframes move2 {
    0% { transform: rotate(0deg) translateX(70px) rotate(0deg) scale(1); }
    50% { transform: rotate(-180deg) translateX(70px) rotate(180deg) scale(1.2); }
    100% { transform: rotate(-360deg) translateX(70px) rotate(360deg) scale(1); }
}

@keyframes move3 {
    0% { transform: rotate(0deg) translateX(30px) rotate(0deg) scale(1); }
    50% { transform: rotate(180deg) translateX(30px) rotate(-180deg) scale(1.2); }
    100% { transform: rotate(360deg) translateX(30px) rotate(-360deg) scale(1); }
}

@keyframes move4 {
    0% { transform: rotate(0deg) translateX(40px) rotate(0deg) scale(1); }
    50% { transform: rotate(-180deg) translateX(40px) rotate(180deg) scale(1.2); }
    100% { transform: rotate(-360deg) translateX(40px) rotate(360deg) scale(1); }
}

/* Enhanced Feature Cards */
.features-section {
    position: relative;
    z-index: 2;
    margin-top: 0;
    padding: 5rem 0;
    background-color: #fff;
}

.section-title {
    margin-bottom: 1rem;
    position: relative;
    display: inline-block;
}

.section-title:after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: linear-gradient(to right, #3498db, #2980b9);
    border-radius: 3px;
}

.section-subtitle {
    max-width: 700px;
    margin: 0 auto 2rem;
}

.feature-card {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    background: white;
    height: 100%;
}

.shadow-hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.shadow-hover:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.feature-icon {
    width: 90px;
    height: 90px;
    border-radius: 30%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
    z-index: 1;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.feature-icon:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: inherit;
    z-index: -1;
    opacity: 0.7;
    transform: scale(1.3) rotate(10deg);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.feature-card:hover .feature-icon:before {
    transform: scale(1) rotate(0deg);
    opacity: 1;
}

.feature-card:hover .feature-icon i {
    transform: scale(1.2) rotate(10deg);
}

.feature-icon i {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Enhanced Timeline */
.how-it-works-section {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    padding: 6rem 0;
}

.timeline {
    position: relative;
    padding: 0;
    list-style: none;
    margin-top: 2rem;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 50px;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, #3498db, #2980b9);
    transform: translateX(-50%);
    border-radius: 4px;
}

.timeline-item {
    position: relative;
    margin-bottom: 3.5rem;
    display: flex;
    align-items: flex-start;
}

.timeline-number {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.5rem;
    z-index: 1;
    flex-shrink: 0;
    box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
    position: relative;
    border: 4px solid white;
}

.timeline-content {
    background-color: white;
    padding: 2rem 2.5rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    margin-left: 2.5rem;
    flex-grow: 1;
    position: relative;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.timeline-item:hover .timeline-content {
    transform: translateY(-8px) scale(1.01);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.timeline-content:before {
    content: '';
    position: absolute;
    top: 25px;
    left: -12px;
    width: 24px;
    height: 24px;
    background: white;
    transform: rotate(45deg);
    box-shadow: -5px 5px 10px rgba(0, 0, 0, 0.05);
}

.timeline-content h4 {
    margin-bottom: 0.75rem;
    color: #2980b9;
    font-weight: 700;
    font-size: 1.5rem;
}

.timeline-content p {
    margin-bottom: 0;
    color: #7f8c8d;
    font-size: 1.1rem;
}

/* Role Cards */
.user-roles-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%);
    position: relative;
}

.role-card {
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
}

.role-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
}

.role-icon {
    width: 80px;
    height: 80px;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.25rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.role-card:hover .role-icon {
    transform: scale(1.1) rotate(10deg);
    background: rgba(52, 152, 219, 0.2);
}

.role-icon i {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.role-card:hover .role-icon i {
    transform: scale(1.2);
    color: #2980b9;
}

.list-group-item {
    border: none;
    padding: 0.85rem 0;
    transition: all 0.3s ease;
    font-size: 1.05rem;
}

.list-group-item i {
    transition: all 0.3s ease;
}

.list-group-item:hover i {
    transform: scale(1.2) rotate(10deg);
    color: #2980b9;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
    position: relative;
    overflow: hidden;
    padding: 6rem 0;
}

.cta-section .container {
    position: relative;
    z-index: 2;
}

/* Button animations */
.pulse-btn {
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.pulse-btn:after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%);
    transform-origin: 50% 50%;
}

.pulse-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.pulse-btn:hover:after {
    animation: ripple 1.5s ease-out;
}

@keyframes ripple {
    0% {
        transform: scale(0, 0);
        opacity: 0.5;
    }
    100% {
        transform: scale(20, 20);
        opacity: 0;
    }
}

.hover-lift {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.hover-lift:hover {
    transform: translateY(-8px) scale(1.05);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .hero-section {
        padding: 5rem 0;
    }
    
    .hero-content {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .d-md-flex.justify-content-md-start {
        justify-content: center !important;
    }
    
    .timeline:before {
        left: 30px;
    }
    
    .timeline-number {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .timeline-content {
        margin-left: 2rem;
        padding: 1.5rem;
    }
    
    .timeline-content:before {
        top: 20px;
    }
}

/* AOS Animation Fallback */
[data-aos] {
    opacity: 1 !important;
    transform: translate(0) scale(1) !important;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #3498db;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #2980b9;
}

/* Badge styling */
.badge {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.badge:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* Section spacing */
section {
    position: relative;
    z-index: 1;
}
</style>

<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 