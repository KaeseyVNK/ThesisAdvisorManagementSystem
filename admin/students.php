<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $studentId = $_GET['id'];
    
    try {
        $conn = getDBConnection();
        
        // Check if student exists
        $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE SinhVienID = :id");
        $stmt->bindParam(':id', $studentId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $student['UserID'];
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Delete from SinhVienGiangVienHuongDan
            $stmt = $conn->prepare("DELETE FROM SinhVienGiangVienHuongDan WHERE SinhVienID = :id");
            $stmt->bindParam(':id', $studentId);
            $stmt->execute();
            
            // Delete from SinhVien
            $stmt = $conn->prepare("DELETE FROM SinhVien WHERE SinhVienID = :id");
            $stmt->bindParam(':id', $studentId);
            $stmt->execute();
            
            // Delete from Users if UserID exists
            if ($userId) {
                $stmt = $conn->prepare("DELETE FROM Users WHERE UserID = :userId");
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            setFlashMessage('Xóa sinh viên thành công', 'success');
        } else {
            setFlashMessage('Không tìm thấy sinh viên', 'danger');
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('../admin/students.php');
}

// Get all students
$conn = getDBConnection();
$stmt = $conn->query("
    SELECT sv.*, u.Username, u.Email as UserEmail,
    (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE SinhVienID = sv.SinhVienID) as AssignmentCount
    FROM SinhVien sv
    LEFT JOIN Users u ON sv.UserID = u.UserID
    ORDER BY sv.HoTen
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý sinh viên</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title">
                        <i class="fas fa-user-graduate me-2"></i>Quản lý sinh viên
                    </h2>
                    <p class="card-text">Quản lý thông tin sinh viên trong hệ thống.</p>
                </div>
                <div>
                    <a href="add-student.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm sinh viên
                    </a>
                </div>
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
                <div class="table-responsive">
                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Họ tên</th>
                                <th>Giới tính</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Khoa</th>
                                <th>Chuyên ngành</th>
                                <th>Niên khóa</th>
                                <th>Trạng thái</th>
                                <th>Tài khoản</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['MaSV']; ?></td>
                                    <td><?php echo $student['HoTen']; ?></td>
                                    <td><?php echo $student['GioiTinh']; ?></td>
                                    <td><?php echo $student['Email']; ?></td>
                                    <td><?php echo $student['SoDienThoai']; ?></td>
                                    <td><?php echo $student['Khoa']; ?></td>
                                    <td><?php echo $student['ChuyenNganh']; ?></td>
                                    <td><?php echo $student['NienKhoa']; ?></td>
                                    <td>
                                        <?php if ($student['TrangThai'] == 'Đang học'): ?>
                                            <span class="badge bg-primary"><?php echo $student['TrangThai']; ?></span>
                                        <?php elseif ($student['TrangThai'] == 'Đã tốt nghiệp'): ?>
                                            <span class="badge bg-success"><?php echo $student['TrangThai']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $student['TrangThai']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['UserID']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i><?php echo $student['Username']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-times me-1"></i>Chưa có
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view-student.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-student.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($student['AssignmentCount'] == 0): ?>
                                                <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $student['SinhVienID']; ?>" data-bs-toggle="tooltip" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="Không thể xóa vì sinh viên đã được phân công">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!$student['UserID']): ?>
                                                <a href="create-account.php?id=<?php echo $student['SinhVienID']; ?>&type=student" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Tạo tài khoản">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa sinh viên này không? Hành động này không thể hoàn tác.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="GET" action="students.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteStudentId">
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const deleteStudentIdInput = document.getElementById('deleteStudentId');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-id');
            deleteStudentIdInput.value = studentId;
            deleteModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 