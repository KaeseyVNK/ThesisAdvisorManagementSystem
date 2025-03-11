<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('ID đề tài không hợp lệ', 'danger');
    redirect('theses.php');
}

$thesisId = $_GET['id'];

try {
    $conn = getDBConnection();
    
    // Get thesis details
    $stmt = $conn->prepare("
        SELECT dt.*,
        (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE DeTaiID = dt.DeTaiID) as SoSinhVien
        FROM DeTai dt
        WHERE dt.DeTaiID = :id
    ");
    $stmt->bindParam(':id', $thesisId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy đề tài', 'danger');
        redirect('theses.php');
    }
    
    $thesis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get assigned students and their advisors
    $stmt = $conn->prepare("
        SELECT sv.MaSV, sv.HoTen as TenSinhVien, sv.Email as EmailSinhVien,
        gv.MaGV, gv.HoTen as TenGiangVien, gv.Email as EmailGiangVien,
        svgv.NgayBatDau, svgv.TrangThai
        FROM SinhVienGiangVienHuongDan svgv
        JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
        JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
        WHERE svgv.DeTaiID = :id
        ORDER BY svgv.NgayBatDau DESC
    ");
    $stmt->bindParam(':id', $thesisId);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('theses.php');
}

// Include header
$pageTitle = 'Chi tiết đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="theses.php">Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chi tiết đề tài</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
                <div>
                    <a href="edit-thesis.php?id=<?php echo $thesisId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </a>
                    <a href="theses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table">
                            <tr>
                                <th width="200">Tên đề tài:</th>
                                <td><?php echo htmlspecialchars($thesis['TenDeTai']); ?></td>
                            </tr>
                            <tr>
                                <th>Mô tả:</th>
                                <td><?php echo nl2br(htmlspecialchars($thesis['MoTa'])); ?></td>
                            </tr>
                            <tr>
                                <th>Lĩnh vực:</th>
                                <td><?php echo htmlspecialchars($thesis['LinhVuc']); ?></td>
                            </tr>
                            <tr>
                                <th>Ngày tạo:</th>
                                <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    <?php if ($thesis['TrangThai'] == 'Đã duyệt'): ?>
                                        <span class="badge bg-success"><?php echo $thesis['TrangThai']; ?></span>
                                    <?php elseif ($thesis['TrangThai'] == 'Chờ duyệt'): ?>
                                        <span class="badge bg-warning"><?php echo $thesis['TrangThai']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?php echo $thesis['TrangThai']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($assignments)): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="mb-3">Sinh viên và giảng viên hướng dẫn</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Tên sinh viên</th>
                                        <th>Mã GV</th>
                                        <th>Tên giảng viên</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['MaSV']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['TenSinhVien']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['MaGV']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['TenGiangVien']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($assignment['NgayBatDau'])); ?></td>
                                        <td>
                                            <?php if ($assignment['TrangThai'] == 'Đang hướng dẫn'): ?>
                                                <span class="badge bg-primary"><?php echo $assignment['TrangThai']; ?></span>
                                            <?php elseif ($assignment['TrangThai'] == 'Đã hoàn thành'): ?>
                                                <span class="badge bg-success"><?php echo $assignment['TrangThai']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?php echo $assignment['TrangThai']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 