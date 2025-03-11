<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $giangVienId = $_POST['giangVienId'];
        $deTaiId = $_POST['deTaiId'];
        $ngayBatDau = $_POST['ngayBatDau'];
        
        // Validate input
        if (empty($giangVienId) || empty($deTaiId) || empty($ngayBatDau)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin');
        }

        // Check if lecturer has reached their maximum student limit
        $stmt = $conn->prepare("
            SELECT SoLuongSinhVienToiDa, 
            (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :id AND TrangThai = 'Đang hướng dẫn') as CurrentCount 
            FROM GiangVien WHERE GiangVienID = :id
        ");
        $stmt->bindParam(':id', $giangVienId);
        $stmt->execute();
        $lecturerInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lecturerInfo['CurrentCount'] >= $lecturerInfo['SoLuongSinhVienToiDa']) {
            throw new Exception('Giảng viên đã đạt số lượng sinh viên tối đa có thể hướng dẫn');
        }

        // Create the assignment
        $stmt = $conn->prepare("
            INSERT INTO SinhVienGiangVienHuongDan (GiangVienID, DeTaiID, NgayBatDau, TrangThai)
            VALUES (:giangVienId, :deTaiId, :ngayBatDau, 'Đang hướng dẫn')
        ");
        
        $stmt->bindParam(':giangVienId', $giangVienId);
        $stmt->bindParam(':deTaiId', $deTaiId);
        $stmt->bindParam(':ngayBatDau', $ngayBatDau);
        $stmt->execute();

        setFlashMessage('Phân công giảng viên thành công', 'success');
        redirect('assignments.php');
    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Get all assignments
$stmt = $conn->query("
    SELECT svgv.*, 
           gv.MaGV, gv.HoTen as TenGiangVien,
           dt.TenDeTai,
           (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID AND TrangThai = 'Đang hướng dẫn') as SoSinhVienHienTai,
           gv.SoLuongSinhVienToiDa
    FROM SinhVienGiangVienHuongDan svgv
    JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.SinhVienID IS NULL
    ORDER BY svgv.NgayBatDau DESC
");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available lecturers
$stmt = $conn->query("
    SELECT GiangVienID, MaGV, HoTen, 
           SoLuongSinhVienToiDa,
           (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = GiangVien.GiangVienID AND TrangThai = 'Đang hướng dẫn') as CurrentCount
    FROM GiangVien
    HAVING CurrentCount < SoLuongSinhVienToiDa
    ORDER BY HoTen
");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available theses
$stmt = $conn->query("
    SELECT DeTaiID, TenDeTai 
    FROM DeTai 
    WHERE TrangThai = 'Đã duyệt'
    ORDER BY TenDeTai
");
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Phân công giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Phân công giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Phân công giảng viên hướng dẫn</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="giangVienId" class="form-label">Giảng viên</label>
                                <select class="form-select" id="giangVienId" name="giangVienId" required>
                                    <option value="">Chọn giảng viên</option>
                                    <?php foreach ($lecturers as $lecturer): ?>
                                        <option value="<?php echo $lecturer['GiangVienID']; ?>">
                                            <?php echo htmlspecialchars($lecturer['MaGV'] . ' - ' . $lecturer['HoTen']); ?>
                                            (<?php echo $lecturer['CurrentCount']; ?>/<?php echo $lecturer['SoLuongSinhVienToiDa']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="deTaiId" class="form-label">Đề tài</label>
                                <select class="form-select" id="deTaiId" name="deTaiId" required>
                                    <option value="">Chọn đề tài</option>
                                    <?php foreach ($theses as $thesis): ?>
                                        <option value="<?php echo $thesis['DeTaiID']; ?>">
                                            <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="ngayBatDau" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="ngayBatDau" name="ngayBatDau" required>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã GV</th>
                                <th>Tên giảng viên</th>
                                <th>Đề tài</th>
                                <th>Ngày bắt đầu</th>
                                <th>Số SV hiện tại</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['MaGV']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['TenGiangVien']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['TenDeTai']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($assignment['NgayBatDau'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $assignment['SoSinhVienHienTai']; ?>/<?php echo $assignment['SoLuongSinhVienToiDa']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $assignment['TrangThai']; ?></span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $assignment['ID']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa phân công này không?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="" method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delete confirmation modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteForm = document.getElementById('deleteForm');
    const deleteId = document.getElementById('deleteId');

    // Handle delete button clicks
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            deleteId.value = this.dataset.id;
            deleteModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 