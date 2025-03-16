<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get faculty details
$facultyDetails = getCurrentUserDetails();

// Kiểm tra nếu không tìm thấy thông tin giảng viên
if (!$facultyDetails) {
    setFlashMessage('Không tìm thấy thông tin giảng viên cho tài khoản của bạn. Vui lòng liên hệ quản trị viên.', 'danger');
    redirect('../dashboard.php');
}

$facultyId = $facultyDetails['GiangVienID'];
$conn = getDBConnection();

// Kiểm tra ID thông báo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID thông báo không hợp lệ', 'danger');
    redirect('notifications.php');
}

$notificationId = $_GET['id'];

// Lấy thông tin thông báo
$stmt = $conn->prepare("
    SELECT * FROM ThongBao 
    WHERE ThongBaoID = :id AND GiangVienID = :facultyId
");
$stmt->bindParam(':id', $notificationId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy thông báo hoặc bạn không có quyền xem thông báo này', 'danger');
    redirect('notifications.php');
}

$notification = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách sinh viên đã nhận thông báo
$stmt = $conn->prepare("
    SELECT sv.SinhVienID, sv.MaSV, sv.HoTen, sv.Email, tbsv.DaDoc, tbsv.NgayDoc
    FROM ThongBaoSinhVien tbsv
    JOIN SinhVien sv ON tbsv.SinhVienID = sv.SinhVienID
    WHERE tbsv.ThongBaoID = :notificationId
    ORDER BY sv.HoTen
");
$stmt->bindParam(':notificationId', $notificationId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="breadcrumb-item"><a href="notifications.php">Quản lý thông báo</a></li>
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
                            <span><i class="fas fa-calendar-alt me-2"></i><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></span>
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
                    
                    <div class="notification-recipients">
                        <h5 class="mb-3">Danh sách sinh viên nhận thông báo</h5>
                        <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian đọc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                        <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                        <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                        <td>
                                            <?php if ($student['DaDoc']): ?>
                                                <span class="badge bg-success">Đã đọc</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa đọc</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['NgayDoc']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($student['NgayDoc'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Không có sinh viên nào nhận thông báo này.
                        </div>
                        <?php endif; ?>
                    </div>
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