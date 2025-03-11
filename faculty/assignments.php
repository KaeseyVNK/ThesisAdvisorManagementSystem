<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Get current faculty ID
$stmt = $conn->prepare("SELECT GiangVienID, SoLuongSinhVienToiDa FROM GiangVien WHERE UserID = :userId");
$stmt->bindParam(':userId', $_SESSION['user_id']);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $faculty['GiangVienID'];

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sinhVienId = $_POST['sinhVienId'];
        $deTaiId = $_POST['deTaiId'];
        $ngayBatDau = $_POST['ngayBatDau'];
        
        // Validate input
        if (empty($sinhVienId) || empty($deTaiId) || empty($ngayBatDau)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin');
        }

        // Check if student is already assigned
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM SinhVienGiangVienHuongDan 
            WHERE SinhVienID = :sinhVienId AND TrangThai = 'Đang hướng dẫn'
        ");
        $stmt->bindParam(':sinhVienId', $sinhVienId);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Sinh viên này đã được phân công giảng viên hướng dẫn');
        }

        // Check if faculty has reached their maximum student limit
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM SinhVienGiangVienHuongDan 
            WHERE GiangVienID = :id AND TrangThai = 'Đang hướng dẫn'
        ");
        $stmt->bindParam(':id', $facultyId);
        $stmt->execute();
        $currentStudents = $stmt->fetchColumn();

        if ($currentStudents >= $faculty['SoLuongSinhVienToiDa']) {
            throw new Exception('Bạn đã đạt số lượng sinh viên tối đa có thể hướng dẫn');
        }

        // Create the assignment
        $stmt = $conn->prepare("
            INSERT INTO SinhVienGiangVienHuongDan 
            (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TrangThai)
            VALUES 
            (:sinhVienId, :giangVienId, :deTaiId, :ngayBatDau, 'Đang hướng dẫn')
        ");
        
        $stmt->bindParam(':sinhVienId', $sinhVienId);
        $stmt->bindParam(':giangVienId', $facultyId);
        $stmt->bindParam(':deTaiId', $deTaiId);
        $stmt->bindParam(':ngayBatDau', $ngayBatDau);
        $stmt->execute();

        setFlashMessage('Phân công sinh viên thành công', 'success');
        redirect('assignments.php');
    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Get available students (not yet assigned)
$stmt = $conn->prepare("
    SELECT SinhVienID, MaSV, HoTen 
    FROM SinhVien 
    WHERE TrangThai = 'Đang học'
    AND SinhVienID NOT IN (
        SELECT SinhVienID 
        FROM SinhVienGiangVienHuongDan 
        WHERE TrangThai = 'Đang hướng dẫn'
    )
    ORDER BY HoTen
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available theses for this faculty
$stmt = $conn->prepare("
    SELECT DISTINCT dt.DeTaiID, dt.TenDeTai 
    FROM DeTai dt
    JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
    AND dt.TrangThai = 'Đã duyệt'
    ORDER BY dt.TenDeTai
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current assignments
$stmt = $conn->prepare("
    SELECT 
        svgv.*,
        sv.MaSV,
        sv.HoTen as TenSinhVien,
        dt.TenDeTai
    FROM SinhVienGiangVienHuongDan svgv
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
    ORDER BY svgv.NgayBatDau DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Phân công sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Phân công sinh viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Phân công sinh viên mới</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sinhVienId" class="form-label">Sinh viên</label>
                                <select class="form-select" id="sinhVienId" name="sinhVienId" required>
                                    <option value="">Chọn sinh viên</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['SinhVienID']; ?>">
                                            <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen']); ?>
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
                                <th>Mã SV</th>
                                <th>Tên sinh viên</th>
                                <th>Đề tài</th>
                                <th>Ngày bắt đầu</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['MaSV']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['TenSinhVien']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['TenDeTai']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($assignment['NgayBatDau'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $assignment['TrangThai'] == 'Đang hướng dẫn' ? 'primary' : 
                                                ($assignment['TrangThai'] == 'Đã hoàn thành' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo $assignment['TrangThai']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="progress.php?student_id=<?php echo $assignment['SinhVienID']; ?>" 
                                               class="btn btn-sm btn-info" title="Xem tiến độ">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary update-status"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateStatusModal"
                                                    data-id="<?php echo $assignment['ID']; ?>"
                                                    data-status="<?php echo $assignment['TrangThai']; ?>"
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

    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            assignmentIdInput.value = id;
            statusSelect.value = status;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>