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

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assignment) {
    $content = sanitizeInput($_POST['content']);
    $status = sanitizeInput($_POST['status']);
    
    // Validate input
    if (empty($content)) {
        $errors[] = 'Vui lòng nhập nội dung cập nhật tiến độ';
    }
    
    if (empty($status)) {
        $errors[] = 'Vui lòng chọn trạng thái tiến độ';
    }
    
    // If no errors, add progress to database
    if (empty($errors)) {
        try {
            $assignmentId = $assignment['ID'];
            
            $stmt = $conn->prepare("
                INSERT INTO TienDo (SinhVienGiangVienID, NoiDung, TrangThai)
                VALUES (:assignmentId, :content, :status)
            ");
            $stmt->bindParam(':assignmentId', $assignmentId);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            $success = 'Cập nhật tiến độ thành công!';
            setFlashMessage($success, 'success');
            redirect('../student/progress.php');
        } catch (PDOException $e) {
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Include header
$pageTitle = 'Cập nhật tiến độ';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="progress.php">Tiến độ luận văn</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cập nhật tiến độ</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle me-2"></i>Cập nhật tiến độ
                </h2>
                <p class="card-text">Cập nhật tiến độ thực hiện luận văn của bạn.</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($assignment): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
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
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Cập nhật tiến độ mới</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="content" class="form-label">Nội dung cập nhật <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="5" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <div class="invalid-feedback">Vui lòng nhập nội dung cập nhật tiến độ</div>
                        <div class="form-text">Mô tả chi tiết về những gì bạn đã hoàn thành, đang thực hiện, hoặc gặp khó khăn.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="" selected disabled>-- Chọn trạng thái --</option>
                            <option value="Đang thực hiện" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                            <option value="Đã hoàn thành" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="Cần chỉnh sửa" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Cần chỉnh sửa') ? 'selected' : ''; ?>>Cần chỉnh sửa</option>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="progress.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lưu cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa được phân công giảng viên hướng dẫn hoặc chưa có đề tài luận văn. Vui lòng liên hệ với quản trị viên để biết thêm chi tiết.
        </div>
        <div class="text-center">
            <a href="../dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i>Quay lại bảng điều khiển
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 