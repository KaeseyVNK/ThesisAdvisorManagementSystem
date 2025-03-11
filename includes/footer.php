    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p>Hệ thống quản lý hướng dẫn luận văn giúp sinh viên và giảng viên theo dõi quá trình hướng dẫn luận văn một cách hiệu quả.</p>
                </div>
                <div class="col-md-3">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>/index.php" class="text-white">Trang chủ</a></li>
                        <li><a href="<?php echo APP_URL; ?>/about.php" class="text-white">Giới thiệu</a></li>
                        <li><a href="<?php echo APP_URL; ?>/contact.php" class="text-white">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Liên hệ</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> Địa chỉ: 123 Đường ABC, Quận XYZ, TP. HCM</p>
                        <p><i class="fas fa-phone me-2"></i> Điện thoại: (028) 1234 5678</p>
                        <p><i class="fas fa-envelope me-2"></i> Email: info@example.com</p>
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
</body>
</html> 