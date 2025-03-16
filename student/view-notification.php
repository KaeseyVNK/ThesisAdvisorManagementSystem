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

// Kiểm tra nếu không tìm thấy thông tin sinh viên
if (!$studentDetails) {
    setFlashMessage('Không tìm thấy thông tin sinh viên cho tài khoản của bạn. Vui lòng liên hệ quản trị viên.', 'danger');
    redirect('../dashboard.php');
}

$studentId = $studentDetails['SinhVienID'];
$conn = getDBConnection();

// Kiểm tra ID thông báo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID thông báo không hợp lệ', 'danger');
    redirect('notifications.php');
}

$notificationId = $_GET['id'];

// Kiểm tra xem thông báo có tồn tại và thuộc về sinh viên này không
$stmt = $conn->prepare("
    SELECT tbsv.ID, tbsv.DaDoc
    FROM ThongBaoSinhVien tbsv
    WHERE tbsv.ThongBaoID = :notificationId AND tbsv.SinhVienID = :studentId
");
$stmt->bindParam(':notificationId', $notificationId);
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy thông báo hoặc bạn không có quyền xem thông báo này', 'danger');
    redirect('notifications.php');
}

$notificationStatus = $stmt->fetch(PDO::FETCH_ASSOC);

// Đánh dấu thông báo đã đọc nếu chưa đọc
if (!$notificationStatus['DaDoc']) {
    $stmt = $conn->prepare("
        UPDATE ThongBaoSinhVien
        SET DaDoc = 1, NgayDoc = NOW()
        WHERE ThongBaoID = :notificationId AND SinhVienID = :studentId
    ");
    $stmt->bindParam(':notificationId', $notificationId);
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();
}

// Lấy thông tin chi tiết thông báo
$stmt = $conn->prepare("
    SELECT 
        tb.ThongBaoID, tb.TieuDe, tb.NoiDung, tb.FileDinhKem, tb.NgayTao,
        gv.GiangVienID, gv.HoTen as TenGiangVien, gv.Email as EmailGiangVien
    FROM ThongBao tb
    JOIN GiangVien gv ON tb.GiangVienID = gv.GiangVienID
    WHERE tb.ThongBaoID = :notificationId
");
$stmt->bindParam(':notificationId', $notificationId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy thông báo', 'danger');
    redirect('notifications.php');
}

$notification = $stmt->fetch(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Chi tiết thông báo';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item"><a href="notifications.php">Thông báo</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết thông báo</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chi tiết thông báo</h5>
                    <a href="notifications.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
                <div class="card-body">
                    <div class="notification-header mb-4">
                        <h4 class="notification-title"><?php echo htmlspecialchars($notification['TieuDe']); ?></h4>
                        <div class="notification-meta text-muted">
                            <div class="mb-2">
                                <i class="fas fa-user me-2"></i>Giảng viên: <?php echo htmlspecialchars($notification['TenGiangVien']); ?>
                                <a href="mailto:<?php echo $notification['EmailGiangVien']; ?>" class="ms-2 text-decoration-none">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($notification['EmailGiangVien']); ?>
                                </a>
                            </div>
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i>Ngày gửi: <?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="notification-content mb-4">
                        <div class="card">
                            <div class="card-body bg-light">
                                <?php echo nl2br(htmlspecialchars($notification['NoiDung'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($notification['FileDinhKem'])): ?>
                    <div class="notification-attachment mb-4">
                        <h5 class="mb-3">File đính kèm</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <p class="mb-1"><?php echo basename($notification['FileDinhKem']); ?></p>
                                        <a href="../<?php echo $notification['FileDinhKem']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download me-1"></i>Tải xuống
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.notification-meta {
    font-size: 0.9rem;
}

.notification-content {
    font-size: 1.05rem;
    line-height: 1.7;
}
</style>

<?php include '../includes/footer.php'; ?> 