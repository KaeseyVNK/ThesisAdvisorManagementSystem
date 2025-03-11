<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Check if thesis ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('ID đề tài không hợp lệ', 'danger');
    redirect('theses.php');
}

$thesisId = $_GET['id'];
$conn = getDBConnection();

// Get current faculty ID
$stmt = $conn->prepare("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
$stmt->bindParam(':userId', $_SESSION['user_id']);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $faculty['GiangVienID'];

// Get thesis details and verify faculty is assigned to this thesis
$stmt = $conn->prepare("
    SELECT dt.*, 
           (SELECT COUNT(*) 
            FROM SinhVienGiangVienHuongDan 
            WHERE DeTaiID = dt.DeTaiID 
            AND GiangVienID = :facultyId) as IsAssigned
    FROM DeTai dt
    WHERE dt.DeTaiID = :thesisId
");
$stmt->bindParam(':thesisId', $thesisId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$thesis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thesis || $thesis['IsAssigned'] == 0) {
    setFlashMessage('Bạn không có quyền truy cập đề tài này', 'danger');
    redirect('theses.php');
}

// Get students working on this thesis under this faculty
$stmt = $conn->prepare("
    SELECT 
        sv.*,
        svgv.ID as AssignmentID,
        svgv.NgayBatDau,
        svgv.NgayKetThuc,
        svgv.TrangThai as AssignmentStatus,
        svgv.GhiChu
    FROM SinhVienGiangVienHuongDan svgv
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    WHERE svgv.DeTaiID = :thesisId 
    AND svgv.GiangVienID = :facultyId
    ORDER BY svgv.NgayBatDau DESC
");
$stmt->bindParam(':thesisId', $thesisId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Sinh viên thực hiện đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="theses.php">Đề tài được phân công</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sinh viên thực hiện đề tài</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                </h4>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($thesis['LinhVuc']); ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-<?php echo $thesis['TrangThai'] == 'Đã duyệt' ? 'success' : ($thesis['TrangThai'] == 'Chờ duyệt' ? 'warning' : 'danger'); ?>">
                                <?php echo $thesis['TrangThai']; ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Mô tả:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($thesis['MoTa'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Danh sách sinh viên thực hiện</h5>
                <a href="assignments.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Thêm sinh viên
                </a>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Mã SV</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                        <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                        <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
                                        <td>
                                            <?php echo $student['NgayKetThuc'] ? date('d/m/Y', strtotime($student['NgayKetThuc'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['AssignmentStatus'] == 'Đang hướng dẫn'): ?>
                                                <span class="badge bg-primary"><?php echo $student['AssignmentStatus']; ?></span>
                                            <?php elseif ($student['AssignmentStatus'] == 'Đã hoàn thành'): ?>
                                                <span class="badge bg-success"><?php echo $student['AssignmentStatus']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?php echo $student['AssignmentStatus']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="progress.php?student_id=<?php echo $student['SinhVienID']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Xem tiến độ">
                                                    <i class="fas fa-tasks"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary update-status" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateStatusModal"
                                                        data-id="<?php echo $student['AssignmentID']; ?>"
                                                        data-status="<?php echo $student['AssignmentStatus']; ?>"
                                                        data-note="<?php echo htmlspecialchars($student['GhiChu']); ?>"
                                                        title="Cập nhật trạng thái">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Chưa có sinh viên nào được phân công cho đề tài này.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật trạng thái</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="update-assignment-status.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="assignmentId">
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Đang hướng dẫn">Đang hướng dẫn</option>
                            <option value="Đã hoàn thành">Đã hoàn thành</option>
                            <option value="Đã hủy">Đã hủy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle update status modal
    const updateStatusModal = document.getElementById('updateStatusModal');
    const assignmentIdInput = document.getElementById('assignmentId');
    const statusSelect = document.getElementById('status');
    const noteTextarea = document.getElementById('note');

    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            const note = this.getAttribute('data-note');
            
            assignmentIdInput.value = id;
            statusSelect.value = status;
            noteTextarea.value = note || '';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 