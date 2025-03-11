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

// Get assignment details
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT svgv.*, dt.TenDeTai, gv.HoTen as GiangVienHoTen
    FROM SinhVienGiangVienHuongDan svgv
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
    LIMIT 1
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Get progress records
$progresses = [];
if ($assignment) {
    $assignmentId = $assignment['ID'];
    $stmt = $conn->prepare("
        SELECT * FROM TienDo
        WHERE SinhVienGiangVienID = :assignmentId
        ORDER BY NgayCapNhat DESC
    ");
    $stmt->bindParam(':assignmentId', $assignmentId);
    $stmt->execute();
    $progresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include header
$pageTitle = 'Tiến độ luận văn';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tiến độ luận văn</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title">
                        <i class="fas fa-tasks me-2"></i>Tiến độ luận văn
                    </h2>
                    <p class="card-text">Theo dõi tiến độ thực hiện luận văn của bạn.</p>
                </div>
                <?php if ($assignment): ?>
                <div>
                    <a href="add-progress.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Cập nhật tiến độ
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($assignment): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> <?php echo $assignment['DeTaiID'] ? $assignment['TenDeTai'] : 'Chưa có đề tài'; ?></p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo $assignment['GiangVienHoTen']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ngày bắt đầu:</strong> <?php echo date('d/m/Y', strtotime($assignment['NgayBatDau'])); ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-primary"><?php echo $assignment['TrangThai']; ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (count($progresses) > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Lịch sử tiến độ</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($progresses as $progress): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($progress['NgayCapNhat'])); ?></div>
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <?php if ($progress['TrangThai'] == 'Đã hoàn thành'): ?>
                                            <span class="badge bg-success me-2"><?php echo $progress['TrangThai']; ?></span>
                                        <?php elseif ($progress['TrangThai'] == 'Đang thực hiện'): ?>
                                            <span class="badge bg-primary me-2"><?php echo $progress['TrangThai']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning me-2"><?php echo $progress['TrangThai']; ?></span>
                                        <?php endif; ?>
                                        Cập nhật tiến độ
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($progress['NoiDung'])); ?></p>
                                    
                                    <?php if (!empty($progress['PhanHoi'])): ?>
                                        <div class="alert alert-info mt-3">
                                            <h6 class="alert-heading"><i class="fas fa-comment-dots me-2"></i>Phản hồi từ giảng viên:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($progress['PhanHoi'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Bạn chưa có cập nhật tiến độ nào. Hãy bắt đầu cập nhật tiến độ thực hiện luận văn của bạn.
        </div>
        <div class="text-center">
            <a href="add-progress.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Cập nhật tiến độ đầu tiên
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa được phân công giảng viên hướng dẫn hoặc chưa có đề tài luận văn. Vui lòng liên hệ với quản trị viên để biết thêm chi tiết.
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 