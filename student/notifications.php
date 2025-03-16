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

// Đánh dấu thông báo đã đọc nếu có id
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
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
    
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notification && !$notification['DaDoc']) {
        // Cập nhật trạng thái đã đọc
        $stmt = $conn->prepare("
            UPDATE ThongBaoSinhVien
            SET DaDoc = 1, NgayDoc = NOW()
            WHERE ThongBaoID = :notificationId AND SinhVienID = :studentId
        ");
        $stmt->bindParam(':notificationId', $notificationId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
    }
}

// Lấy danh sách thông báo
$stmt = $conn->prepare("
    SELECT 
        tb.ThongBaoID, tb.TieuDe, tb.NoiDung, tb.FileDinhKem, tb.NgayTao,
        gv.HoTen as TenGiangVien,
        tbsv.DaDoc, tbsv.NgayDoc
    FROM ThongBaoSinhVien tbsv
    JOIN ThongBao tb ON tbsv.ThongBaoID = tb.ThongBaoID
    JOIN GiangVien gv ON tb.GiangVienID = gv.GiangVienID
    WHERE tbsv.SinhVienID = :studentId
    ORDER BY tb.NgayTao DESC
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số thông báo chưa đọc
$stmt = $conn->prepare("
    SELECT COUNT(*) as UnreadCount
    FROM ThongBaoSinhVien
    WHERE SinhVienID = :studentId AND DaDoc = 0
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['UnreadCount'];

// Include header
$pageTitle = 'Thông báo';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thông báo</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($_SESSION['flash_messages'])): ?>
        <?php foreach ($_SESSION['flash_messages'] as $message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash_messages']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Thông báo
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> chưa đọc</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($notifications) > 0): ?>
                    <div class="list-group notification-list">
                        <?php foreach ($notifications as $notification): ?>
                        <a href="view-notification.php?id=<?php echo $notification['ThongBaoID']; ?>" class="list-group-item list-group-item-action <?php echo !$notification['DaDoc'] ? 'unread' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h5 class="mb-1">
                                    <?php if (!$notification['DaDoc']): ?>
                                    <span class="badge bg-primary me-2">Mới</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($notification['TieuDe']); ?>
                                </h5>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></small>
                            </div>
                            <p class="mb-1 notification-preview"><?php echo htmlspecialchars(substr($notification['NoiDung'], 0, 150)) . (strlen($notification['NoiDung']) > 150 ? '...' : ''); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($notification['TenGiangVien']); ?>
                                    <?php if (!empty($notification['FileDinhKem'])): ?>
                                    <span class="ms-2"><i class="fas fa-paperclip"></i> Có đính kèm</span>
                                    <?php endif; ?>
                                </small>
                                <?php if ($notification['DaDoc']): ?>
                                <small class="text-success"><i class="fas fa-check me-1"></i> Đã đọc</small>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa có thông báo nào.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-list .list-group-item.unread {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
    border-left: 4px solid var(--bs-primary);
}

.notification-preview {
    color: #666;
    font-size: 0.95rem;
}

.notification-list .list-group-item:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>

<?php include '../includes/footer.php'; ?> 