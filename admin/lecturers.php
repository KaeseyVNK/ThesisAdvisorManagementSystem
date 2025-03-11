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
    $lecturerId = $_GET['id'];
    
    try {
        $conn = getDBConnection();
        
        // Check if lecturer exists
        $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE GiangVienID = :id");
        $stmt->bindParam(':id', $lecturerId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $lecturer['UserID'];
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Check if lecturer has any assignments
            $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :id");
            $stmt->bindParam(':id', $lecturerId);
            $stmt->execute();
            $assignmentCount = $stmt->fetchColumn();
            
            if ($assignmentCount > 0) {
                throw new Exception('Không thể xóa giảng viên vì đang có sinh viên được phân công hướng dẫn');
            }
            
            // Delete from GiangVien
            $stmt = $conn->prepare("DELETE FROM GiangVien WHERE GiangVienID = :id");
            $stmt->bindParam(':id', $lecturerId);
            $stmt->execute();
            
            // Delete from Users if UserID exists
            if ($userId) {
                $stmt = $conn->prepare("DELETE FROM Users WHERE UserID = :userId");
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            setFlashMessage('Xóa giảng viên thành công', 'success');
        } else {
            setFlashMessage('Không tìm thấy giảng viên', 'danger');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('../admin/lecturers.php');
}

// Get all lecturers
$conn = getDBConnection();
$stmt = $conn->query("
    SELECT gv.*, u.Username, u.Email as UserEmail,
    (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID) as SoSinhVienHuongDan
    FROM GiangVien gv
    LEFT JOIN Users u ON gv.UserID = u.UserID
    ORDER BY gv.HoTen
");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý giảng viên</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Quản lý giảng viên
                    </h2>
                    <p class="card-text">Quản lý thông tin giảng viên trong hệ thống.</p>
                </div>
                <div>
                    <a href="add-lecturer.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm giảng viên
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
                <h5 class="card-title mb-0">Danh sách giảng viên</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>Mã GV</th>
                                <th>Họ tên</th>
                                <th>Học vị</th>
                                <th>Chức vụ</th>
                                <th>Email</th>
                                <th>Khoa</th>
                                <th>Chuyên ngành</th>
                                <th>SV Hướng dẫn</th>
                                <th>Tài khoản</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lecturer['MaGV']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['HoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['HocVi']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['ChucVu']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['Khoa']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['ChuyenNganh']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $lecturer['SoSinhVienHuongDan']; ?>/<?php echo $lecturer['SoLuongSinhVienToiDa']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($lecturer['UserID']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i><?php echo $lecturer['Username']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-times me-1"></i>Chưa có
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view-lecturer.php?id=<?php echo $lecturer['GiangVienID']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-lecturer.php?id=<?php echo $lecturer['GiangVienID']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($lecturer['SoSinhVienHuongDan'] == 0): ?>
                                                <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $lecturer['GiangVienID']; ?>" data-bs-toggle="tooltip" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="Không thể xóa vì đang có sinh viên được phân công">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!$lecturer['UserID']): ?>
                                                <a href="create-account.php?id=<?php echo $lecturer['GiangVienID']; ?>&type=faculty" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Tạo tài khoản">
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
                Bạn có chắc chắn muốn xóa giảng viên này không? Hành động này không thể hoàn tác.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="GET" action="lecturers.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteLecturerId">
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
    const deleteLecturerId = document.getElementById('deleteLecturerId');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            deleteLecturerId.value = id;
            deleteModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 