<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a faculty member
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get faculty details
$facultyDetails = getCurrentUserDetails();
$facultyId = $facultyDetails['GiangVienID'];

// Get students assigned to this faculty
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT sv.*, svgv.ID as AssignmentID, svgv.NgayBatDau, svgv.TrangThai as AssignmentStatus, dt.TenDeTai
    FROM SinhVienGiangVienHuongDan svgv
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
    ORDER BY svgv.TrangThai ASC, svgv.NgayBatDau DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Sinh viên hướng dẫn';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sinh viên hướng dẫn</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-user-graduate me-2"></i>Sinh viên hướng dẫn
                </h2>
                <p class="card-text">Danh sách sinh viên được phân công cho bạn hướng dẫn.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách sinh viên</h5>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Mã SV</th>
                                    <th>Họ tên</th>
                                    <th>Khoa</th>
                                    <th>Chuyên ngành</th>
                                    <th>Đề tài</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['MaSV']; ?></td>
                                        <td><?php echo $student['HoTen']; ?></td>
                                        <td><?php echo $student['Khoa']; ?></td>
                                        <td><?php echo $student['ChuyenNganh']; ?></td>
                                        <td><?php echo $student['TenDeTai'] ?? 'Chưa có đề tài'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
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
                                            <a href="student-detail.php?id=<?php echo $student['AssignmentID']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Chi tiết
                                            </a>
                                            <a href="progress.php?student_id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-tasks"></i> Tiến độ
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa được phân công sinh viên nào để hướng dẫn.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Student Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Cập nhật trạng thái hướng dẫn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update-status.php" method="POST">
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
    // Update status modal
    var updateStatusButtons = document.querySelectorAll('.btn-update-status');
    updateStatusButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var assignmentId = this.getAttribute('data-id');
            var status = this.getAttribute('data-status');
            var note = this.getAttribute('data-note');
            
            document.getElementById('assignmentId').value = assignmentId;
            document.getElementById('status').value = status;
            document.getElementById('note').value = note || '';
            
            var modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 