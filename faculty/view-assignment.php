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

// Kiểm tra ID bài tập
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID bài tập không hợp lệ', 'danger');
    redirect('assignments.php');
}

$assignmentId = $_GET['id'];

// Lấy thông tin bài tập
$stmt = $conn->prepare("
    SELECT * FROM BaiTap 
    WHERE BaiTapID = :id AND GiangVienID = :facultyId
");
$stmt->bindParam(':id', $assignmentId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('Không tìm thấy bài tập hoặc bạn không có quyền xem bài tập này', 'danger');
    redirect('assignments.php');
}

$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý đánh giá bài tập đã nộp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {
    $submissionId = $_POST['submission_id'];
    $score = $_POST['score'];
    $feedback = trim($_POST['feedback']);
    $status = $_POST['status'];
    
    try {
        // Kiểm tra quyền đánh giá
        $stmt = $conn->prepare("
            SELECT nb.NopBaiTapID 
            FROM NopBaiTap nb
            JOIN BaiTap bt ON nb.BaiTapID = bt.BaiTapID
            WHERE nb.NopBaiTapID = :submissionId 
            AND bt.GiangVienID = :facultyId
        ");
        $stmt->bindParam(':submissionId', $submissionId);
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->prepare("
                UPDATE NopBaiTap 
                SET DiemSo = :score, NhanXet = :feedback, TrangThai = :status
                WHERE NopBaiTapID = :submissionId
            ");
            $stmt->bindParam(':score', $score);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':submissionId', $submissionId);
            $stmt->execute();
            
            setFlashMessage('Đã đánh giá bài tập thành công', 'success');
        } else {
            setFlashMessage('Bạn không có quyền đánh giá bài tập này', 'danger');
        }
    } catch (Exception $e) {
        setFlashMessage('Đã xảy ra lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('view-assignment.php?id=' . $assignmentId);
}

// Lấy danh sách sinh viên được giao bài tập
$stmt = $conn->prepare("
    SELECT 
        sv.SinhVienID, sv.MaSV, sv.HoTen, sv.Email, 
        btsv.DaDoc, btsv.NgayDoc,
        nb.NopBaiTapID, nb.FileNop, nb.GhiChu, nb.NgayNop, nb.DiemSo, nb.NhanXet, nb.TrangThai
    FROM BaiTapSinhVien btsv
    JOIN SinhVien sv ON btsv.SinhVienID = sv.SinhVienID
    LEFT JOIN NopBaiTap nb ON btsv.BaiTapID = nb.BaiTapID AND btsv.SinhVienID = nb.SinhVienID
    WHERE btsv.BaiTapID = :assignmentId
    ORDER BY sv.HoTen
");
$stmt->bindParam(':assignmentId', $assignmentId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Chi tiết bài tập';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item"><a href="assignments.php">Quản lý bài tập</a></li>
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
        <div class="col-md-12">
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
                            <span class="me-3"><i class="fas fa-calendar-alt me-2"></i>Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($assignment['NgayTao'])); ?></span>
                            <span><i class="fas fa-clock me-2"></i>Hạn nộp: <?php echo date('d/m/Y H:i', strtotime($assignment['HanNop'])); ?></span>
                            <?php 
                            $deadline = new DateTime($assignment['HanNop']);
                            $now = new DateTime();
                            if ($deadline < $now) {
                                echo ' <span class="badge bg-danger ms-2">Đã hết hạn</span>';
                            } elseif ($deadline->diff($now)->days < 2) {
                                echo ' <span class="badge bg-warning text-dark ms-2">Sắp hết hạn</span>';
                            }
                            ?>
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
                    
                    <div class="assignment-submissions">
                        <h5 class="mb-3">Danh sách sinh viên và bài nộp</h5>
                        <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ tên</th>
                                        <th>Trạng thái đọc</th>
                                        <th>Trạng thái nộp</th>
                                        <th>Ngày nộp</th>
                                        <th>Điểm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                        <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                        <td>
                                            <?php if ($student['DaDoc']): ?>
                                                <span class="badge bg-success">Đã đọc</span>
                                                <small class="d-block text-muted"><?php echo date('d/m/Y H:i', strtotime($student['NgayDoc'])); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa đọc</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($student['NopBaiTapID'])): ?>
                                                <?php if ($student['TrangThai'] == 'Đã chấm'): ?>
                                                    <span class="badge bg-success">Đã chấm</span>
                                                <?php elseif ($student['TrangThai'] == 'Cần sửa'): ?>
                                                    <span class="badge bg-warning text-dark">Cần sửa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Đã nộp</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Chưa nộp</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($student['NgayNop'])): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($student['NgayNop'])); ?>
                                                <?php 
                                                $submissionDate = new DateTime($student['NgayNop']);
                                                if ($submissionDate > $deadline) {
                                                    echo ' <span class="badge bg-danger">Nộp trễ</span>';
                                                }
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($student['DiemSo'])): ?>
                                                <span class="fw-bold"><?php echo $student['DiemSo']; ?>/10</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($student['NopBaiTapID'])): ?>
                                                <button type="button" class="btn btn-sm btn-primary view-submission" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewSubmissionModal"
                                                        data-id="<?php echo $student['NopBaiTapID']; ?>"
                                                        data-student="<?php echo htmlspecialchars($student['HoTen'] ?? ''); ?>"
                                                        data-file="<?php echo $student['FileNop'] ?? ''; ?>"
                                                        data-notes="<?php echo htmlspecialchars($student['GhiChu'] ?? ''); ?>"
                                                        data-date="<?php echo date('d/m/Y H:i', strtotime($student['NgayNop'] ?? 'now')); ?>"
                                                        data-score="<?php echo $student['DiemSo'] ?? ''; ?>"
                                                        data-feedback="<?php echo htmlspecialchars($student['NhanXet'] ?? ''); ?>"
                                                        data-status="<?php echo $student['TrangThai'] ?? ''; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-eye-slash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Không có sinh viên nào được giao bài tập này.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem bài nộp -->
<div class="modal fade" id="viewSubmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết bài nộp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="submission-info">
                            <h5 class="student-name mb-3"></h5>
                            <div class="submission-meta text-muted mb-3">
                                <span class="submission-date"></span>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Ghi chú của sinh viên</h6>
                                </div>
                                <div class="card-body bg-light">
                                    <p class="submission-notes"></p>
                                </div>
                            </div>
                            
                            <div class="submission-file mb-3">
                                <h6>File bài nộp</h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <p class="file-name mb-1"></p>
                                        <a href="#" class="download-link btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-download me-1"></i>Tải xuống
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form action="view-assignment.php?id=<?php echo $assignmentId; ?>" method="POST" id="gradeForm">
                    <input type="hidden" name="action" value="grade">
                    <input type="hidden" name="submission_id" id="submissionId">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="score" class="form-label">Điểm (0-10)</label>
                                <input type="number" class="form-control" id="score" name="score" min="0" max="10" step="0.5" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Đã chấm">Đã chấm</option>
                                    <option value="Cần sửa">Cần sửa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Nhận xét</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" form="gradeForm" class="btn btn-primary">Lưu đánh giá</button>
            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý modal xem bài nộp
    const viewSubmissionModal = document.getElementById('viewSubmissionModal');
    if (viewSubmissionModal) {
        viewSubmissionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const student = button.getAttribute('data-student');
            const file = button.getAttribute('data-file');
            const notes = button.getAttribute('data-notes') || 'Không có ghi chú';
            const date = button.getAttribute('data-date');
            const score = button.getAttribute('data-score');
            const feedback = button.getAttribute('data-feedback') || '';
            const status = button.getAttribute('data-status') || 'Đã nộp';
            
            // Cập nhật thông tin trong modal
            viewSubmissionModal.querySelector('.student-name').textContent = student;
            viewSubmissionModal.querySelector('.submission-date').textContent = 'Nộp lúc: ' + date;
            viewSubmissionModal.querySelector('.submission-notes').textContent = notes;
            viewSubmissionModal.querySelector('.file-name').textContent = file ? file.split('/').pop() : 'Không có file';
            
            const downloadLink = viewSubmissionModal.querySelector('.download-link');
            if (file) {
                downloadLink.href = '../' + file;
                downloadLink.style.display = 'inline-block';
            } else {
                downloadLink.style.display = 'none';
            }
            
            // Cập nhật form đánh giá
            document.getElementById('submissionId').value = id;
            document.getElementById('score').value = score || '';
            document.getElementById('feedback').value = feedback;
            document.getElementById('status').value = status;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 