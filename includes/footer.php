    </main>
    
    <?php if (!isset($hideHeader)): ?>
    <footer class="mt-auto">
        <div class="container">
            <div class="row g-4 py-5">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3"><?php echo APP_NAME; ?></h5>
                    <p class="text-white-50">Hệ thống quản lý hướng dẫn luận văn hiện đại, giúp sinh viên và giảng viên theo dõi quá trình hướng dẫn luận văn một cách hiệu quả.</p>
                    <div class="social-icons">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-3">Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/index.php"><i class="fas fa-angle-right me-2"></i>Trang chủ</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/about.php"><i class="fas fa-angle-right me-2"></i>Giới thiệu</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/contact.php"><i class="fas fa-angle-right me-2"></i>Liên hệ</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/faq.php"><i class="fas fa-angle-right me-2"></i>Hỏi đáp</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-3">Hỗ trợ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/help.php"><i class="fas fa-angle-right me-2"></i>Trợ giúp</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/terms.php"><i class="fas fa-angle-right me-2"></i>Điều khoản</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>/privacy.php"><i class="fas fa-angle-right me-2"></i>Bảo mật</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">Liên hệ</h5>
                    <div class="d-flex mb-3">
                        <div class="icon-box me-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <p class="mb-0">123 Đường ABC, Quận XYZ, TP. HCM</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="icon-box me-3">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <p class="mb-0">(028) 1234 5678</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="icon-box me-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <p class="mb-0">info@example.com</p>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="mt-0">
            <div class="row py-3">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Bản quyền thuộc về Trường Đại học XYZ.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Thiết kế và phát triển bởi <a href="#" class="text-white">Nhóm 1103</a></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" class="btn btn-primary back-to-top" title="Lên đầu trang">
        <i class="fas fa-arrow-up"></i>
    </button>
    <?php endif; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            if (dropdown && typeof bootstrap !== 'undefined') {
                new bootstrap.Dropdown(dropdown);
            }
        });
        
        // Initialize all tooltips
        var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(function(tooltip) {
            if (tooltip && typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(tooltip);
            }
        });
        
        // Back to top button
        var backToTopButton = document.getElementById('back-to-top');
        if (backToTopButton) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            });
            
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    });
    </script>
    
    <?php if (!isset($hideHeader)): ?>
    <style>
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        transform: translateY(-5px);
    }
    </style>
    <?php endif; ?>
</body>
</html> 