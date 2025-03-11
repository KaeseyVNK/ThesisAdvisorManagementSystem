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
    $thesisId = $_GET['id'];
    
    try {
        $conn = getDBConnection();
        
        // Check if thesis exists
        $stmt = $conn->prepare("SELECT * FROM DeTai WHERE DeTaiID = :id");
        $stmt->bindParam(':id', $thesisId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Check if thesis is assigned to any student
            $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE DeTaiID = :id");
            $stmt->bindParam(':id', $thesisId);
            $stmt->execute();
            $assignmentCount = $stmt->fetchColumn();
            
            if ($assignmentCount > 0) {
                throw new Exception('Không thể xóa đề tài vì đang được phân công cho sinh viên');
            }
            
            // Delete thesis
            $stmt = $conn->prepare("DELETE FROM DeTai WHERE DeTaiID = :id");
            $stmt->bindParam(':id', $thesisId);
            $stmt->execute();
            
            setFlashMessage('Xóa đề tài thành công', 'success');
        } else {
            setFlashMessage('Không tìm thấy đề tài', 'danger');
        }
    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('../admin/theses.php');
}

// Get all theses with assignment count
$conn = getDBConnection();
$stmt = $conn->query("
    SELECT dt.*, 
    (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE DeTaiID = dt.DeTaiID) as AssignmentCount
    FROM DeTai dt
    ORDER BY dt.NgayTao DESC
");
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý đề tài</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title">
                        <i class="fas fa-book me-2"></i>Quản lý đề tài
                    </h2>
                    <p class="card-text">Quản lý thông tin đề tài luận văn trong hệ thống.</p>
                </div>
                <div>
                    <a href="add-thesis.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm đề tài
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
                <h5 class="card-title mb-0">Danh sách đề tài</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đề tài</th>
                                <th>Lĩnh vực</th>
                                <th>Ngày tạo</th>
                                <th>Trạng thái</th>
                                <th>Sinh viên</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($theses as $thesis): ?>
                                <tr>
                                    <td><?php echo $thesis['DeTaiID']; ?></td>
                                    <td><?php echo htmlspecialchars($thesis['TenDeTai']); ?></td>
                                    <td><?php echo htmlspecialchars($thesis['LinhVuc']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                                    <td>
                                        <?php if ($thesis['TrangThai'] == 'Đã duyệt'): ?>
                                            <span class="badge bg-success"><?php echo $thesis['TrangThai']; ?></span>
                                        <?php elseif ($thesis['TrangThai'] == 'Chờ duyệt'): ?>
                                            <span class="badge bg-warning"><?php echo $thesis['TrangThai']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $thesis['TrangThai']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $thesis['AssignmentCount']; ?> sinh viên
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view-thesis.php?id=<?php echo $thesis['DeTaiID']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-thesis.php?id=<?php echo $thesis['DeTaiID']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($thesis['AssignmentCount'] == 0): ?>
                                                <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $thesis['DeTaiID']; ?>" data-bs-toggle="tooltip" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="Không thể xóa vì đề tài đã được phân công">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                Bạn có chắc chắn muốn xóa đề tài này không? Hành động này không thể hoàn tác.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="GET" action="theses.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteThesisId">
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
    const deleteThesisId = document.getElementById('deleteThesisId');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            deleteThesisId.value = id;
            deleteModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 