<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Check if proposal ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('Không tìm thấy đề xuất', 'danger');
    redirect('student-proposals.php');
}

$proposalId = $_GET['id'];

// Verify the proposal exists
$stmt = $conn->prepare("
    SELECT dx.*, sv.HoTen as TenSinhVien, sv.MaSV, gv.HoTen as TenGiangVien
    FROM DeTaiSinhVienDeXuat dx
    JOIN SinhVien sv ON dx.SinhVienID = sv.SinhVienID
    LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
    WHERE dx.DeXuatID = :proposalId
");
$stmt->bindParam(':proposalId', $proposalId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy đề xuất', 'danger');
    redirect('student-proposals.php');
}

$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if proposal is already processed
if ($proposal['TrangThai'] != 'Chờ duyệt') {
    setFlashMessage('Đề xuất này đã được xử lý', 'warning');
    redirect('student-proposals.php');
}

// Get all available faculty members with their current student count
$stmt = $conn->prepare("
    SELECT 
        gv.GiangVienID, 
        gv.HoTen, 
        gv.ChuyenNganh,
        gv.SoLuongSinhVienToiDa,
        COUNT(sv.SinhVienID) as SoSinhVien
    FROM 
        GiangVien gv
    LEFT JOIN 
        SinhVienGiangVienHuongDan svgv ON gv.GiangVienID = svgv.GiangVienID AND svgv.TrangThai = 'Đang hướng dẫn'
    LEFT JOIN 
        SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    GROUP BY 
        gv.GiangVienID
    ORDER BY 
        gv.HoTen
");
$stmt->execute();
$faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['faculty_id'])) {
        $facultyId = $_POST['faculty_id'];
        
        // Update proposal with assigned faculty
        $stmt = $conn->prepare("
            UPDATE DeTaiSinhVienDeXuat 
            SET GiangVienID = :facultyId
            WHERE DeXuatID = :proposalId
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->bindParam(':facultyId', $facultyId);
        
        if ($stmt->execute()) {
            // Create notification for faculty
            $stmt = $conn->prepare("
                INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                VALUES (:facultyId, :title, :content)
            ");
            $title = 'Đề xuất đề tài mới cần duyệt';
            $content = "Bạn được phân công xem xét đề xuất đề tài '{$proposal['TenDeTai']}'. Vui lòng xem xét và phản hồi.";
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->execute();
            
            $notificationId = $conn->lastInsertId();
            
            // Thêm thông báo cho giảng viên
            $stmt = $conn->prepare("
                INSERT INTO ThongBaoGiangVien (ThongBaoID, GiangVienID)
                VALUES (:notificationId, :facultyId)
            ");
            $stmt->bindParam(':notificationId', $notificationId);
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->execute();
            
            // Create notification for student
            $stmt = $conn->prepare("
                INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                VALUES (1, :title, :content)
            ");
            $title = 'Đề xuất đề tài đã được phân công giảng viên';
            $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã được phân công cho giảng viên. Vui lòng chờ phản hồi.";
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
            
            setFlashMessage('Đã phân công giảng viên thành công', 'success');
            redirect('student-proposals.php');
        } else {
            setFlashMessage('Có lỗi xảy ra khi phân công giảng viên', 'danger');
        }
    } else {
        setFlashMessage('Vui lòng chọn giảng viên', 'danger');
    }
}

// Include header
$pageTitle = 'Phân công giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="student-proposals.php">Đề xuất đề tài từ sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Phân công giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Phân công giảng viên
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Phân công giảng viên hướng dẫn cho đề xuất đề tài sau:</p>
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
                <div class="mb-3">
                    <h6>Giảng viên hiện tại:</h6>
                    <p>
                        <?php if ($proposal['GiangVienID']): ?>
                            <?php echo htmlspecialchars($proposal['TenGiangVien']); ?>
                        <?php else: ?>
                            <span class="text-muted">Chưa phân công</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="faculty_id" class="form-label">Giảng viên hướng dẫn <span class="text-danger">*</span></label>
                        <select class="form-select" id="faculty_id" name="faculty_id" required>
                            <option value="">-- Chọn giảng viên --</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo $faculty['GiangVienID']; ?>" <?php echo ($proposal['GiangVienID'] == $faculty['GiangVienID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['HoTen']); ?> 
                                    (<?php echo htmlspecialchars($faculty['ChuyenNganh']); ?>) - 
                                    Đang hướng dẫn: <?php echo $faculty['SoSinhVien']; ?>/<?php echo $faculty['SoLuongSinhVienToiDa']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>Sau khi phân công, giảng viên sẽ nhận được thông báo và có thể xem xét đề xuất này.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="student-proposals.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu phân công
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 