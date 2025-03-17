<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Get current faculty ID
$stmt = $conn->prepare("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
$stmt->bindParam(':userId', $_SESSION['user_id']);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $faculty['GiangVienID'];

// Check if proposal ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('Không tìm thấy đề xuất', 'danger');
    redirect('view-student-proposals.php');
}

$proposalId = $_GET['id'];

// Verify the proposal exists and belongs to this faculty
$stmt = $conn->prepare("
    SELECT dx.*, sv.HoTen as TenSinhVien, sv.MaSV
    FROM DeTaiSinhVienDeXuat dx
    JOIN SinhVien sv ON dx.SinhVienID = sv.SinhVienID
    WHERE dx.DeXuatID = :proposalId AND dx.GiangVienID = :facultyId
");
$stmt->bindParam(':proposalId', $proposalId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy đề xuất hoặc bạn không có quyền thực hiện hành động này', 'danger');
    redirect('view-student-proposals.php');
}

$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if proposal is already processed
if ($proposal['TrangThai'] != 'Chờ duyệt') {
    setFlashMessage('Đề xuất này đã được xử lý', 'warning');
    redirect('view-student-proposals.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate reject reason
    if (empty($_POST['reject_reason'])) {
        setFlashMessage('Vui lòng nhập lý do từ chối', 'danger');
    } else {
        $rejectReason = $_POST['reject_reason'];
        
        // Update proposal status
        $stmt = $conn->prepare("
            UPDATE DeTaiSinhVienDeXuat 
            SET TrangThai = 'Từ chối', LyDoTuChoi = :reason, NgayDuyet = NOW() 
            WHERE DeXuatID = :proposalId
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->bindParam(':reason', $rejectReason);
        
        if ($stmt->execute()) {
            // Create notification for student
            $stmt = $conn->prepare("
                INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                VALUES (:facultyId, :title, :content)
            ");
            $title = 'Đề xuất đề tài bị từ chối';
            $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã bị từ chối. Lý do: $rejectReason";
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->execute();
            
            $notificationId = $conn->lastInsertId();
            
            // Thêm thông báo cho sinh viên
            $stmt = $conn->prepare("
                INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID)
                VALUES (:notificationId, :studentId)
            ");
            $stmt->bindParam(':notificationId', $notificationId);
            $stmt->bindParam(':studentId', $proposal['SinhVienID']);
            $stmt->execute();
            
            setFlashMessage('Đã từ chối đề xuất đề tài', 'success');
            redirect('view-student-proposals.php');
        } else {
            setFlashMessage('Có lỗi xảy ra khi từ chối đề xuất', 'danger');
        }
    }
}

// Include header
$pageTitle = 'Từ chối đề xuất đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="view-student-proposals.php">Đề xuất đề tài từ sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Từ chối đề xuất</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-times-circle me-2"></i>Từ chối đề xuất đề tài
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Bạn đang từ chối đề xuất đề tài sau:</p>
                </div>
                
                <div class="mb-3">
                    <h6>Sinh viên:</h6>
                    <p><?php echo htmlspecialchars($proposal['TenSinhVien']); ?> (<?php echo htmlspecialchars($proposal['MaSV']); ?>)</p>
                </div>
                <div class="mb-3">
                    <h6>Tên đề tài:</h6>
                    <p><?php echo htmlspecialchars($proposal['TenDeTai']); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Lĩnh vực:</h6>
                    <p><?php echo htmlspecialchars($proposal['LinhVuc']); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Mô tả:</h6>
                    <p><?php echo nl2br(htmlspecialchars($proposal['MoTa'])); ?></p>
                </div>
                
                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required></textarea>
                        <div class="form-text">Vui lòng cung cấp lý do từ chối để sinh viên có thể hiểu và cải thiện đề xuất trong tương lai.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="view-student-proposals.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Xác nhận từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 