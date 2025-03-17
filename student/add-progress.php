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

// Lấy thông tin trạng thái đề tài của sinh viên
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT sv.*, u.Email as UserEmail
    FROM SinhVien sv
    LEFT JOIN Users u ON sv.UserID = u.UserID
    WHERE sv.SinhVienID = :studentId
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$studentStatus = $stmt->fetch(PDO::FETCH_ASSOC);

// Get assignment details
$stmt = $conn->prepare("
    SELECT svgv.*, dt.TenDeTai, dt.MoTa as MoTaDeTai, dt.LinhVuc, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
    FROM SinhVienGiangVienHuongDan svgv
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
    LIMIT 1
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra xem sinh viên có đề xuất đề tài đã được duyệt không
if (!$assignment) {
    $stmt = $conn->prepare("
        SELECT dx.*, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
        FROM DeTaiSinhVienDeXuat dx
        LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
        WHERE dx.SinhVienID = :studentId AND dx.TrangThai = 'Đã duyệt'
        ORDER BY dx.NgayDuyet DESC
        LIMIT 1
    ");
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();
    $approvedProposal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nếu sinh viên có trạng thái "Đã có đề tài" nhưng không tìm thấy thông tin assignment,
    // thử tìm thông tin đề tài từ bảng DeTai dựa trên đề xuất đã được duyệt
    if (!$approvedProposal && isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài') {
        // Tìm đề tài mới nhất được tạo cho sinh viên này từ bảng SinhVienGiangVienHuongDan
        $stmt = $conn->prepare("
            SELECT dt.*, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
            FROM DeTai dt
            LEFT JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
            LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE svgv.SinhVienID = :studentId
            ORDER BY dt.NgayTao DESC
            LIMIT 1
        ");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $approvedThesis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu không tìm thấy từ SinhVienGiangVienHuongDan, tìm từ DeTaiSinhVienDeXuat đã được duyệt
        if (!$approvedThesis) {
            $stmt = $conn->prepare("
                SELECT dt.*, dx.GiangVienID, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
                FROM DeTai dt
                JOIN DeTaiSinhVienDeXuat dx ON dt.TenDeTai = dx.TenDeTai AND dt.MoTa = dx.MoTa
                LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
                WHERE dx.SinhVienID = :studentId AND dx.TrangThai = 'Đã duyệt'
                ORDER BY dt.NgayTao DESC
                LIMIT 1
            ");
            $stmt->bindParam(':studentId', $studentId);
            $stmt->execute();
            $approvedThesis = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Process form submission
$errors = [];
$success = '';

$canAddProgress = $assignment || (isset($approvedThesis) && $approvedThesis);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canAddProgress) {
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
            if ($assignment) {
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
            } elseif (isset($approvedThesis) && $approvedThesis) {
                // Tạo bản ghi mới trong SinhVienGiangVienHuongDan nếu chưa có
                $stmt = $conn->prepare("
                    SELECT ID FROM SinhVienGiangVienHuongDan 
                    WHERE SinhVienID = :studentId AND DeTaiID = :thesisId
                ");
                $stmt->bindParam(':studentId', $studentId);
                $stmt->bindParam(':thesisId', $approvedThesis['DeTaiID']);
                $stmt->execute();
                $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existingAssignment) {
                    // Tạo bản ghi mới trong SinhVienGiangVienHuongDan
                    $stmt = $conn->prepare("
                        INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TrangThai)
                        VALUES (:studentId, :facultyId, :thesisId, NOW(), 'Đang hướng dẫn')
                    ");
                    $stmt->bindParam(':studentId', $studentId);
                    $stmt->bindParam(':facultyId', $approvedThesis['GiangVienID']);
                    $stmt->bindParam(':thesisId', $approvedThesis['DeTaiID']);
                    $stmt->execute();
                    
                    $assignmentId = $conn->lastInsertId();
                } else {
                    $assignmentId = $existingAssignment['ID'];
                }
                
                // Thêm tiến độ mới
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
            } else {
                $errors[] = 'Không thể cập nhật tiến độ vì thông tin đề tài chưa được cập nhật đầy đủ. Vui lòng thử lại sau.';
            }
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

<?php 
$canAddProgress = $assignment || (isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài');
if ($assignment): 
?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> 
                            <?php 
                            if (!empty($assignment['TenDeTai'])) {
                                echo htmlspecialchars($assignment['TenDeTai']);
                            } elseif (isset($approvedProposal) && !empty($approvedProposal['TenDeTai'])) {
                                echo htmlspecialchars($approvedProposal['TenDeTai']);
                            } elseif (isset($approvedThesis) && !empty($approvedThesis['TenDeTai'])) {
                                echo htmlspecialchars($approvedThesis['TenDeTai']);
                            } elseif (isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài') {
                                echo '<span class="text-success">Đã có đề tài (đang cập nhật thông tin)</span>';
                            } else {
                                echo 'Chưa có đề tài';
                            }
                            ?>
                        </p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo htmlspecialchars($assignment['GiangVienHoTen'] ?? ''); ?></p>
                        <p><strong>Email giảng viên:</strong> <?php echo htmlspecialchars($assignment['GiangVienEmail'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($assignment['LinhVuc'] ?? ''); ?></p>
                        <p><strong>Ngày bắt đầu:</strong> <?php echo !empty($assignment['NgayBatDau']) ? date('d/m/Y', strtotime($assignment['NgayBatDau'])) : 'Chưa xác định'; ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-primary"><?php echo $assignment['TrangThai'] ?? 'Chưa xác định'; ?></span></p>
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
                            <option value="" selected disabled>Chọn trạng thái</option>
                            <option value="Đang thực hiện" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                            <option value="Đã hoàn thành" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="Gặp khó khăn" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Gặp khó khăn') ? 'selected' : ''; ?>>Gặp khó khăn</option>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Cập nhật tiến độ</button>
                        <a href="../student/progress.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài'): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Đề tài của bạn đã được duyệt và đang được cập nhật trong hệ thống. Vui lòng làm mới trang sau vài phút để xem thông tin chi tiết.
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Đề tài:</strong> <span class="text-success">Đã có đề tài</span></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-success">Đã duyệt</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Cập nhật tiến độ mới</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Không thể cập nhật tiến độ vì thông tin đề tài chưa được cập nhật đầy đủ. Vui lòng thử lại sau.
                </div>
                <div class="d-grid gap-2">
                    <a href="../student/progress.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($approvedThesis) && $approvedThesis): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Đề tài của bạn đã được duyệt. Bạn có thể bắt đầu cập nhật tiến độ.
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> <?php echo htmlspecialchars($approvedThesis['TenDeTai'] ?? ''); ?></p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo htmlspecialchars($approvedThesis['GiangVienHoTen'] ?? ''); ?></p>
                        <p><strong>Email giảng viên:</strong> <?php echo htmlspecialchars($approvedThesis['GiangVienEmail'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($approvedThesis['LinhVuc'] ?? ''); ?></p>
                        <p><strong>Ngày tạo:</strong> <?php echo !empty($approvedThesis['NgayTao']) ? date('d/m/Y', strtotime($approvedThesis['NgayTao'])) : 'Chưa xác định'; ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-success">Đã duyệt</span></p>
                    </div>
                </div>
                <?php if (!empty($approvedThesis['MoTa'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Mô tả đề tài:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($approvedThesis['MoTa'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
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
                            <option value="" selected disabled>Chọn trạng thái</option>
                            <option value="Đang thực hiện" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                            <option value="Đã hoàn thành" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="Gặp khó khăn" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Gặp khó khăn') ? 'selected' : ''; ?>>Gặp khó khăn</option>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Cập nhật tiến độ</button>
                        <a href="../student/progress.php" class="btn btn-secondary">Quay lại</a>
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
            <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa có đề tài luận văn. Vui lòng <a href="../student/thesis.php">đăng ký đề tài</a> hoặc <a href="../student/thesis.php">đề xuất đề tài mới</a>.
        </div>
        <div class="d-grid gap-2">
            <a href="../student/progress.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 