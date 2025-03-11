<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get student details
$studentDetails = getCurrentUserDetails();
$studentId = $studentDetails['SinhVienID'];

// Get advisor details
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT gv.*, svgv.NgayBatDau, svgv.NgayKetThuc, svgv.TrangThai, svgv.GhiChu, dt.TenDeTai, dt.MoTa as DeTaiMoTa
    FROM SinhVienGiangVienHuongDan svgv
    JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
    LIMIT 1
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$advisor = $stmt->fetch(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Giảng viên hướng dẫn';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Giảng viên hướng dẫn</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên hướng dẫn
                </h2>
                <p class="card-text">Thông tin về giảng viên hướng dẫn luận văn của bạn.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($advisor): ?>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin giảng viên</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="../assets/images/avatar-placeholder.png" alt="<?php echo $advisor['HoTen']; ?>" class="profile-img">
                </div>
                <h4><?php echo $advisor['HocVi'] . ' ' . $advisor['HoTen']; ?></h4>
                <p class="text-muted"><?php echo $advisor['ChucVu']; ?></p>
                <hr>
                <div class="text-start">
                    <p><i class="fas fa-id-card me-2"></i> <strong>Mã GV:</strong> <?php echo $advisor['MaGV']; ?></p>
                    <p><i class="fas fa-envelope me-2"></i> <strong>Email:</strong> <?php echo $advisor['Email']; ?></p>
                    <p><i class="fas fa-phone me-2"></i> <strong>Điện thoại:</strong> <?php echo $advisor['SoDienThoai']; ?></p>
                    <p><i class="fas fa-graduation-cap me-2"></i> <strong>Khoa:</strong> <?php echo $advisor['Khoa']; ?></p>
                    <p><i class="fas fa-book me-2"></i> <strong>Chuyên ngành:</strong> <?php echo $advisor['ChuyenNganh']; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin hướng dẫn</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Ngày bắt đầu:</strong>
                    </div>
                    <div class="col-md-8">
                        <?php echo date('d/m/Y', strtotime($advisor['NgayBatDau'])); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Ngày kết thúc (dự kiến):</strong>
                    </div>
                    <div class="col-md-8">
                        <?php echo $advisor['NgayKetThuc'] ? date('d/m/Y', strtotime($advisor['NgayKetThuc'])) : 'Chưa xác định'; ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Trạng thái:</strong>
                    </div>
                    <div class="col-md-8">
                        <span class="badge bg-primary"><?php echo $advisor['TrangThai']; ?></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Ghi chú:</strong>
                    </div>
                    <div class="col-md-8">
                        <?php echo $advisor['GhiChu'] ? $advisor['GhiChu'] : 'Không có ghi chú'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Đề tài luận văn</h5>
            </div>
            <div class="card-body">
                <?php if (isset($advisor['TenDeTai'])): ?>
                    <h4><?php echo $advisor['TenDeTai']; ?></h4>
                    <p class="text-muted"><?php echo $advisor['DeTaiMoTa']; ?></p>
                    <a href="../student/thesis.php" class="btn btn-primary">Xem chi tiết đề tài</a>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa có đề tài luận văn.
                    </div>
                    <a href="../student/thesis.php" class="btn btn-primary">Đăng ký đề tài</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Bạn chưa được phân công giảng viên hướng dẫn. Vui lòng liên hệ với quản trị viên để biết thêm chi tiết.
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 