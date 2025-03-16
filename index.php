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
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4 hover-lift">
                        <i class="fas fa-user-plus me-2"></i>Đăng ký
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-delay="200">
                <div class="hero-image-container">
                    <img src="assets/images/thesis.svg" alt="Thesis Illustration" class="img-fluid floating">
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
                            <h4>Đăng ký tài khoản</h4>
                            <p>Sinh viên và giảng viên đăng ký tài khoản trên hệ thống với thông tin cá nhân và học thuật.</p>
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
                <p class="lead mb-4">Đăng ký tài khoản để trải nghiệm hệ thống quản lý hướng dẫn luận văn hiện đại và hiệu quả.</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="login.php" class="btn btn-light btn-lg px-4 me-sm-3 pulse-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4 hover-lift">
                        <i class="fas fa-user-plus me-2"></i>Đăng ký
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
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    overflow: hidden;
}

.text-gradient {
    background: linear-gradient(to right, #fff, #e0e0e0);
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
    animation: floating 3s ease-in-out infinite;
}

@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
    100% { transform: translateY(0px); }
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
    width: 300px;
    height: 300px;
    top: -150px;
    right: -50px;
    animation: move1 15s linear infinite;
}

.shape-2 {
    width: 200px;
    height: 200px;
    bottom: -100px;
    left: 10%;
    animation: move2 20s linear infinite;
}

.shape-3 {
    width: 150px;
    height: 150px;
    bottom: 20%;
    right: 10%;
    animation: move3 25s linear infinite;
}

.shape-4 {
    width: 100px;
    height: 100px;
    top: 30%;
    left: 20%;
    animation: move4 30s linear infinite;
}

@keyframes move1 {
    0% { transform: rotate(0deg) translateX(50px) rotate(0deg); }
    100% { transform: rotate(360deg) translateX(50px) rotate(-360deg); }
}

@keyframes move2 {
    0% { transform: rotate(0deg) translateX(70px) rotate(0deg); }
    100% { transform: rotate(-360deg) translateX(70px) rotate(360deg); }
}

@keyframes move3 {
    0% { transform: rotate(0deg) translateX(30px) rotate(0deg); }
    100% { transform: rotate(360deg) translateX(30px) rotate(-360deg); }
}

@keyframes move4 {
    0% { transform: rotate(0deg) translateX(40px) rotate(0deg); }
    100% { transform: rotate(-360deg) translateX(40px) rotate(360deg); }
}

/* Enhanced Feature Cards */
.features-section {
    position: relative;
    z-index: 2;
    margin-top: -50px;
}

.section-title {
    margin-bottom: 1rem;
    position: relative;
    display: inline-block;
}

.section-subtitle {
    max-width: 700px;
    margin: 0 auto;
}

.feature-card {
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
}

.shadow-hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.shadow-hover:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
    z-index: 1;
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
    transform: scale(1.3);
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon:before {
    transform: scale(1);
    opacity: 1;
}

.feature-card:hover .feature-icon i {
    transform: scale(1.2);
}

.feature-icon i {
    transition: all 0.3s ease;
}

/* Enhanced Timeline */
.how-it-works-section {
    position: relative;
    overflow: hidden;
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
    left: 50px;
    width: 3px;
    height: 100%;
    background: linear-gradient(to bottom, var(--secondary-color), var(--secondary-dark));
    transform: translateX(-50%);
    border-radius: 3px;
}

.timeline-item {
    position: relative;
    margin-bottom: 3rem;
    display: flex;
    align-items: flex-start;
}

.timeline-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-dark) 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
    z-index: 1;
    flex-shrink: 0;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    position: relative;
}

.timeline-content {
    background-color: white;
    padding: 1.5rem 2rem;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    margin-left: 2rem;
    flex-grow: 1;
    position: relative;
    transition: all 0.3s ease;
}

.timeline-item:hover .timeline-content {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.timeline-content:before {
    content: '';
    position: absolute;
    top: 20px;
    left: -10px;
    width: 20px;
    height: 20px;
    background: white;
    transform: rotate(45deg);
    box-shadow: -5px 5px 10px rgba(0, 0, 0, 0.05);
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

/* Role Cards */
.role-card {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.role-icon {
    width: 60px;
    height: 60px;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    transition: all 0.3s ease;
}

.role-card:hover .role-icon {
    transform: scale(1.1);
    background: rgba(52, 152, 219, 0.2);
}

.role-icon i {
    transition: all 0.3s ease;
}

.role-card:hover .role-icon i {
    transform: rotate(10deg);
}

.list-group-item {
    border: none;
    padding: 0.75rem 0;
    transition: all 0.3s ease;
}

.list-group-item i {
    transition: all 0.3s ease;
}

.list-group-item:hover i {
    transform: scale(1.2);
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    position: relative;
    overflow: hidden;
}

.cta-section:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('assets/images/pattern.svg');
    opacity: 0.05;
    z-index: 1;
}

.cta-section .container {
    position: relative;
    z-index: 2;
}

/* Button animations */
.pulse-btn {
    position: relative;
    overflow: hidden;
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
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.1);
}

/* AOS Animation Fallback */
[data-aos] {
    opacity: 1 !important;
    transform: translate(0) scale(1) !important;
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