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

// Kiểm tra ID tiến độ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID tiến độ không hợp lệ', 'danger');
    redirect('progress.php');
}

$progressId = $_GET['id'];

// Lấy thông tin chi tiết tiến độ
$stmt = $conn->prepare("
    SELECT 
        td.TienDoID, td.NoiDung, td.TrangThai, td.NgayCapNhat, td.PhanHoi,
        sv.SinhVienID, sv.HoTen as TenSinhVien, sv.MaSV, sv.Email as EmailSinhVien,
        dt.DeTaiID, dt.TenDeTai,
        svgv.ID as SinhVienGiangVienID
    FROM TienDo td
    JOIN SinhVienGiangVienHuongDan svgv ON td.SinhVienGiangVienID = svgv.ID
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE td.TienDoID = :progressId AND svgv.GiangVienID = :facultyId
");
$stmt->bindParam(':progressId', $progressId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy tiến độ hoặc bạn không có quyền xem tiến độ này', 'danger');
    redirect('progress.php');
}

$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý cập nhật trạng thái và ghi chú
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'];
    $newComment = $_POST['comment'];
    
    $stmt = $conn->prepare("
        UPDATE TienDo
        SET TrangThai = :status, PhanHoi = :comment
        WHERE TienDoID = :progressId
    ");
    $stmt->bindParam(':status', $newStatus);
    $stmt->bindParam(':comment', $newComment);
    $stmt->bindParam(':progressId', $progressId);
    
    if ($stmt->execute()) {
        setFlashMessage('Cập nhật trạng thái tiến độ thành công', 'success');
        // Cập nhật lại thông tin tiến độ sau khi cập nhật
        $progress['TrangThai'] = $newStatus;
        $progress['PhanHoi'] = $newComment;
    } else {
        setFlashMessage('Có lỗi xảy ra khi cập nhật trạng thái tiến độ', 'danger');
    }
}

// Include header
$pageTitle = 'Chi tiết tiến độ';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item"><a href="progress.php">Quản lý tiến độ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết tiến độ</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tasks me-2 text-primary"></i>Chi tiết tiến độ
                    </h5>
                    <a href="progress.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin sinh viên</h6>
                            <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($progress['TenSinhVien']); ?></p>
                            <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($progress['MaSV']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($progress['EmailSinhVien']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin đề tài</h6>
                            <?php if (!empty($progress['TenDeTai'])): ?>
                                <p><strong>Tên đề tài:</strong> <?php echo htmlspecialchars($progress['TenDeTai']); ?></p>
                            <?php else: ?>
                                <p class="text-muted">Sinh viên chưa được phân công đề tài</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="mb-3">Thông tin tiến độ</h6>
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-<?php echo $progress['TrangThai'] == 'Đã hoàn thành' ? 'success' : ($progress['TrangThai'] == 'Đang thực hiện' ? 'primary' : 'warning'); ?> me-2">
                                            <?php echo $progress['TrangThai']; ?>
                                        </span>
                                        <small class="text-muted">Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($progress['NgayCapNhat'])); ?></small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="progress-content mb-3">
                                        <?php echo nl2br(htmlspecialchars($progress['NoiDung'])); ?>
                                    </div>
                                    
                                    <?php if (!empty($progress['PhanHoi'])): ?>
                                    <div class="progress-comment mt-4">
                                        <h6 class="mb-2">Ghi chú của giảng viên</h6>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <?php echo nl2br(htmlspecialchars($progress['PhanHoi'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Cập nhật trạng thái</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Trạng thái</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="Đang thực hiện" <?php echo $progress['TrangThai'] == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                                                <option value="Đã hoàn thành" <?php echo $progress['TrangThai'] == 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                                <option value="Cần chỉnh sửa" <?php echo $progress['TrangThai'] == 'Cần chỉnh sửa' ? 'selected' : ''; ?>>Cần chỉnh sửa</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="comment" class="form-label">Ghi chú</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="4"><?php echo htmlspecialchars($progress['PhanHoi']); ?></textarea>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Cập nhật
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-content {
    font-size: 1.05rem;
    line-height: 1.7;
}
</style>

<?php include '../includes/footer.php'; ?> 