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

// Kiểm tra ID bài tập
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID bài tập không hợp lệ', 'danger');
    redirect('assignments.php');
}

$assignmentId = $_GET['id'];

// Kiểm tra xem bài tập có tồn tại và thuộc về sinh viên này không
$stmt = $conn->prepare("
    SELECT btsv.DaDoc, btsv.NgayDoc
    FROM BaiTapSinhVien btsv
    WHERE btsv.BaiTapID = :assignmentId AND btsv.SinhVienID = :studentId
");
$stmt->bindParam(':assignmentId', $assignmentId);
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy bài tập hoặc bạn không có quyền xem bài tập này', 'danger');
    redirect('assignments.php');
}

$assignmentStatus = $stmt->fetch(PDO::FETCH_ASSOC);

// Đánh dấu bài tập đã đọc nếu chưa đọc
if (!$assignmentStatus['DaDoc']) {
    $stmt = $conn->prepare("
        UPDATE BaiTapSinhVien 
        SET DaDoc = 1, NgayDoc = NOW() 
        WHERE BaiTapID = :assignmentId AND SinhVienID = :studentId
    ");
    $stmt->bindParam(':assignmentId', $assignmentId);
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();
}

// Lấy thông tin chi tiết bài tập
$stmt = $conn->prepare("
    SELECT 
        bt.BaiTapID, bt.TieuDe, bt.MoTa, bt.FileDinhKem, bt.HanNop, bt.NgayTao,
        gv.GiangVienID, gv.HoTen as TenGiangVien, gv.Email as EmailGiangVien
    FROM BaiTap bt
    JOIN GiangVien gv ON bt.GiangVienID = gv.GiangVienID
    WHERE bt.BaiTapID = :assignmentId
");
$stmt->bindParam(':assignmentId', $assignmentId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy bài tập', 'danger');
    redirect('assignments.php');
}

$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thông tin bài nộp (nếu có)
$stmt = $conn->prepare("
    SELECT * FROM NopBaiTap
    WHERE BaiTapID = :assignmentId AND SinhVienID = :studentId
");
$stmt->bindParam(':assignmentId', $assignmentId);
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý nộp bài tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $notes = trim($_POST['notes']);
    
    try {
        $conn->beginTransaction();
        
        // Upload file if provided
        $submissionFile = null;
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
            $uploadDir = '../uploads/submissions/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . $studentId . '_' . basename($_FILES['submission_file']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetFile)) {
                $submissionFile = 'uploads/submissions/' . $fileName;
            } else {
                throw new Exception('Không thể tải lên file bài nộp');
            }
        } else {
            throw new Exception('Vui lòng chọn file bài nộp');
        }
        
        // Kiểm tra xem đã nộp bài chưa
        if ($submission) {
            // Cập nhật bài nộp
            $stmt = $conn->prepare("
                UPDATE NopBaiTap
                SET FileNop = :file, GhiChu = :notes, NgayNop = NOW(), TrangThai = 'Đã nộp'
                WHERE BaiTapID = :assignmentId AND SinhVienID = :studentId
            ");
        } else {
            // Tạo bài nộp mới
            $stmt = $conn->prepare("
                INSERT INTO NopBaiTap (BaiTapID, SinhVienID, FileNop, GhiChu, NgayNop, TrangThai)
                VALUES (:assignmentId, :studentId, :file, :notes, NOW(), 'Đã nộp')
            ");
        }
        
        $stmt->bindParam(':assignmentId', $assignmentId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->bindParam(':file', $submissionFile);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
        
        $conn->commit();
        setFlashMessage('Bài tập đã được nộp thành công', 'success');
        redirect('view-assignment.php?id=' . $assignmentId);
    } catch (Exception $e) {
        $conn->rollBack();
        setFlashMessage('Đã xảy ra lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Include header
$pageTitle = 'Chi tiết bài tập';
include '../includes/header.php';

// Kiểm tra hạn nộp
$now = new DateTime();
$deadline = new DateTime($assignment['HanNop']);
$isExpired = $deadline < $now;
$isSubmitted = !empty($submission);
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item"><a href="assignments.php">Bài tập</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết bài tập</li>
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
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chi tiết bài tập</h5>
                    <a href="assignments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
                <div class="card-body">
                    <div class="assignment-header mb-4">
                        <h4 class="assignment-title"><?php echo htmlspecialchars($assignment['TieuDe']); ?></h4>
                        <div class="assignment-meta text-muted">
                            <div class="mb-2">
                                <i class="fas fa-user me-2"></i>Giảng viên: <?php echo htmlspecialchars($assignment['TenGiangVien']); ?>
                                <a href="mailto:<?php echo $assignment['EmailGiangVien']; ?>" class="ms-2 text-decoration-none">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($assignment['EmailGiangVien']); ?>
                                </a>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-calendar-alt me-2"></i>Ngày giao: <?php echo date('d/m/Y H:i', strtotime($assignment['NgayTao'])); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock me-2"></i>Hạn nộp: <?php echo date('d/m/Y H:i', strtotime($assignment['HanNop'])); ?>
                                <?php if ($isExpired): ?>
                                <span class="badge bg-danger ms-2">Đã hết hạn</span>
                                <?php elseif ($deadline->diff($now)->days < 2): ?>
                                <span class="badge bg-warning text-dark ms-2">Sắp hết hạn</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="assignment-content mb-4">
                        <h5 class="mb-3">Mô tả bài tập</h5>
                        <div class="card">
                            <div class="card-body bg-light">
                                <?php echo nl2br(htmlspecialchars($assignment['MoTa'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($assignment['FileDinhKem'])): ?>
                    <div class="assignment-attachment mb-4">
                        <h5 class="mb-3">File đính kèm</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <p class="mb-1"><?php echo basename($assignment['FileDinhKem']); ?></p>
                                        <a href="../<?php echo $assignment['FileDinhKem']; ?>" target="_blank" class="btn btn-sm btn-primary">
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
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trạng thái bài tập</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Trạng thái:</span>
                        <?php if ($isSubmitted): ?>
                            <?php if ($submission['TrangThai'] == 'Đã chấm'): ?>
                                <span class="badge bg-success">Đã chấm</span>
                            <?php elseif ($submission['TrangThai'] == 'Cần sửa'): ?>
                                <span class="badge bg-warning text-dark">Cần sửa</span>
                            <?php else: ?>
                                <span class="badge bg-info">Đã nộp</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-danger">Chưa nộp</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isSubmitted): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Ngày nộp:</span>
                        <span><?php echo date('d/m/Y H:i', strtotime($submission['NgayNop'])); ?></span>
                    </div>
                    
                    <?php if ($submission['NgayNop'] > $assignment['HanNop']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Bạn đã nộp trễ bài tập này.
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($submission['DiemSo'])): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Điểm:</span>
                        <span class="fw-bold"><?php echo $submission['DiemSo']; ?>/10</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($submission['NhanXet'])): ?>
                    <div class="mb-3">
                        <h6>Nhận xét của giảng viên:</h6>
                        <div class="card">
                            <div class="card-body bg-light">
                                <?php echo nl2br(htmlspecialchars($submission['NhanXet'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <h6>File bài nộp:</h6>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                            <div>
                                <p class="mb-1"><?php echo basename($submission['FileNop']); ?></p>
                                <a href="../<?php echo $submission['FileNop']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download me-1"></i>Tải xuống
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$isExpired || $isSubmitted): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#submitAssignmentModal">
                            <?php echo $isSubmitted ? '<i class="fas fa-edit me-2"></i>Nộp lại bài tập' : '<i class="fas fa-paper-plane me-2"></i>Nộp bài tập'; ?>
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Bài tập đã hết hạn nộp.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal nộp bài tập -->
<div class="modal fade" id="submitAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $isSubmitted ? 'Nộp lại bài tập' : 'Nộp bài tập'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="view-assignment.php?id=<?php echo $assignmentId; ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="submit">
                    
                    <div class="mb-3">
                        <label for="submission_file" class="form-label">File bài nộp <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="submission_file" name="submission_file" required>
                        <div class="form-text">Tải lên file bài làm của bạn (PDF, Word, ZIP, v.v.)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Nhập ghi chú hoặc thông tin bổ sung về bài nộp của bạn (nếu có)"><?php echo $isSubmitted ? htmlspecialchars($submission['GhiChu']) : ''; ?></textarea>
                    </div>
                    
                    <?php if ($isSubmitted && $submission['NgayNop'] > $assignment['HanNop']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Bạn đã nộp trễ bài tập này. Việc nộp lại có thể ảnh hưởng đến điểm của bạn.
                    </div>
                    <?php elseif (!$isSubmitted && $deadline->diff($now)->days < 1): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Bài tập sắp hết hạn nộp. Hãy nộp sớm để tránh bị trễ.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i><?php echo $isSubmitted ? 'Nộp lại' : 'Nộp bài'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.assignment-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.assignment-meta {
    font-size: 0.9rem;
}

.assignment-content {
    font-size: 1.05rem;
    line-height: 1.7;
}
</style>

<?php include '../includes/footer.php'; ?> 