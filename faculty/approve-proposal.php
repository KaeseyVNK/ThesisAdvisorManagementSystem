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
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Update proposal status
        $stmt = $conn->prepare("
            UPDATE DeTaiSinhVienDeXuat 
            SET TrangThai = 'Đã duyệt', NgayDuyet = NOW() 
            WHERE DeXuatID = :proposalId
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->execute();
        
        // Create a new thesis from the proposal
        $stmt = $conn->prepare("
            INSERT INTO DeTai (TenDeTai, MoTa, LinhVuc, TrangThai, NgayTao)
            VALUES (:title, :description, :field, 'Đã duyệt', NOW())
        ");
        $stmt->bindParam(':title', $proposal['TenDeTai']);
        $stmt->bindParam(':description', $proposal['MoTa']);
        $stmt->bindParam(':field', $proposal['LinhVuc']);
        $stmt->execute();
        
        $newThesisId = $conn->lastInsertId();
        
        // Kiểm tra xem sinh viên đã có bản ghi trong bảng SinhVienGiangVienHuongDan chưa
        $stmt = $conn->prepare("
            SELECT ID FROM SinhVienGiangVienHuongDan 
            WHERE SinhVienID = :studentId AND TrangThai = 'Đang hướng dẫn'
        ");
        $stmt->bindParam(':studentId', $proposal['SinhVienID']);
        $stmt->execute();
        $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingAssignment) {
            // Cập nhật bản ghi đã tồn tại
            $stmt = $conn->prepare("
                UPDATE SinhVienGiangVienHuongDan 
                SET GiangVienID = :facultyId, DeTaiID = :thesisId, NgayBatDau = NOW()
                WHERE ID = :assignmentId
            ");
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->bindParam(':thesisId', $newThesisId);
            $stmt->bindParam(':assignmentId', $existingAssignment['ID']);
            $stmt->execute();
        } else {
            // Tạo bản ghi mới
            $stmt = $conn->prepare("
                INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TrangThai)
                VALUES (:studentId, :facultyId, :thesisId, NOW(), 'Đang hướng dẫn')
            ");
            $stmt->bindParam(':studentId', $proposal['SinhVienID']);
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->bindParam(':thesisId', $newThesisId);
            $stmt->execute();
        }
        
        // Cập nhật trạng thái sinh viên đã có đề tài
        $stmt = $conn->prepare("
            UPDATE SinhVien 
            SET TrangThaiDeTai = 'Đã có đề tài'
            WHERE SinhVienID = :studentId
        ");
        $stmt->bindParam(':studentId', $proposal['SinhVienID']);
        $stmt->execute();
        
        // Create notification for student
        $stmt = $conn->prepare("
            INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
            VALUES (:facultyId, :title, :content)
        ");
        $title = 'Đề xuất đề tài được chấp nhận';
        $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã được chấp nhận.";
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
        
        $conn->commit();
        setFlashMessage('Đã duyệt đề xuất đề tài thành công', 'success');
        redirect('view-student-proposals.php');
    } catch (Exception $e) {
        $conn->rollBack();
        setFlashMessage('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
        redirect('view-student-proposals.php');
    }
}

// Include header
$pageTitle = 'Xác nhận duyệt đề xuất';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="view-student-proposals.php">Đề xuất đề tài từ sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Xác nhận duyệt đề xuất</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>Xác nhận duyệt đề xuất
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Bạn đang xác nhận duyệt đề xuất đề tài sau:</p>
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
                
                <div class="alert alert-warning">
                    <p><strong>Lưu ý:</strong> Khi duyệt, hệ thống sẽ tự động tạo đề tài mới và phân công sinh viên này cho bạn hướng dẫn.</p>
                </div>
                
                <form method="post" class="mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="view-student-proposals.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Xác nhận duyệt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 