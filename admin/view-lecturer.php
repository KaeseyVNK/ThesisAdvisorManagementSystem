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
    setFlashMessage('ID giảng viên không hợp lệ', 'danger');
    redirect('lecturers.php');
}

$lecturerId = $_GET['id'];

try {
    $conn = getDBConnection();
    
    // Get lecturer details with user information
    $stmt = $conn->prepare("
        SELECT gv.*, u.Username, u.Email as UserEmail,
        (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID) as SoSinhVienHuongDan
        FROM GiangVien gv
        LEFT JOIN Users u ON gv.UserID = u.UserID
        WHERE gv.GiangVienID = :id
    ");
    $stmt->bindParam(':id', $lecturerId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy giảng viên', 'danger');
        redirect('lecturers.php');
    }
    
    $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get students being advised
    $stmt = $conn->prepare("
        SELECT sv.MaSV, sv.HoTen, sv.Email, sv.ChuyenNganh,
        svgv.NgayBatDau, svgv.TrangThai,
        dt.TenDeTai
        FROM SinhVienGiangVienHuongDan svgv
        JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
        LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
        WHERE svgv.GiangVienID = :id
        ORDER BY svgv.NgayBatDau DESC
    ");
    $stmt->bindParam(':id', $lecturerId);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('lecturers.php');
}

// Include header
$pageTitle = 'Chi tiết giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="lecturers.php">Quản lý giảng viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chi tiết giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Thông tin giảng viên</h5>
                <div>
                    <a href="edit-lecturer.php?id=<?php echo $lecturerId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </a>
                    <a href="lecturers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Thông tin cá nhân</h6>
                        <table class="table">
                            <tr>
                                <th width="30%">Mã giảng viên:</th>
                                <td><?php echo htmlspecialchars($lecturer['MaGV'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                            <tr>
                                <th>Họ và tên:</th>
                                <td><?php echo htmlspecialchars($lecturer['HoTen'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                            <tr>
                                <th>Giới tính:</th>
                                <td><?php echo isset($lecturer['GioiTinh']) && !empty($lecturer['GioiTinh']) ? htmlspecialchars($lecturer['GioiTinh']) : 'Chưa cập nhật'; ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($lecturer['Email'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                            <tr>
                                <th>Số điện thoại:</th>
                                <td><?php echo htmlspecialchars($lecturer['SoDienThoai'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Thông tin học thuật</h6>
                        <table class="table">
                            <tr>
                                <th width="30%">Khoa:</th>
                                <td><?php echo htmlspecialchars($lecturer['Khoa'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                            <tr>
                                <th>Chuyên ngành:</th>
                                <td><?php echo htmlspecialchars($lecturer['ChuyenNganh'] ?? 'Chưa cập nhật'); ?></td>
                            </tr>
                            <tr>
                                <th>Học hàm/Học vị:</th>
                                <td><?php echo isset($lecturer['HocHamHocVi']) && !empty($lecturer['HocHamHocVi']) ? htmlspecialchars($lecturer['HocHamHocVi']) : 'Chưa cập nhật'; ?></td>
                            </tr>
                            <tr>
                                <th>Lĩnh vực nghiên cứu:</th>
                                <td><?php echo isset($lecturer['LinhVucNghienCuu']) && !empty($lecturer['LinhVucNghienCuu']) ? htmlspecialchars($lecturer['LinhVucNghienCuu']) : 'Chưa cập nhật'; ?></td>
                            </tr>
                            <tr>
                                <th>Số sinh viên hướng dẫn:</th>
                                <td>
                                    <span class="badge bg-primary"><?php echo $lecturer['SoSinhVienHuongDan'] ?? 0; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="fw-bold">Thông tin tài khoản</h6>
                        <table class="table">
                            <tr>
                                <th width="15%">Tài khoản:</th>
                                <td>
                                    <?php if ($lecturer['UserID']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i><?php echo htmlspecialchars($lecturer['Username']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-times me-1"></i>Chưa có tài khoản
                                        </span>
                                        <a href="create-account.php?id=<?php echo $lecturerId; ?>&type=lecturer" class="btn btn-sm btn-success ms-2">
                                            <i class="fas fa-user-plus me-1"></i>Tạo tài khoản
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($students)): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="fw-bold">Sinh viên đang hướng dẫn</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Chuyên ngành</th>
                                        <th>Đề tài</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                        <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                        <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['ChuyenNganh']); ?></td>
                                        <td><?php echo htmlspecialchars($student['TenDeTai'] ?? 'Chưa có đề tài'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
                                        <td>
                                            <?php if ($student['TrangThai'] == 'Đang hướng dẫn'): ?>
                                                <span class="badge bg-primary"><?php echo $student['TrangThai']; ?></span>
                                            <?php elseif ($student['TrangThai'] == 'Đã hoàn thành'): ?>
                                                <span class="badge bg-success"><?php echo $student['TrangThai']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo $student['TrangThai']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Giảng viên này chưa hướng dẫn sinh viên nào.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 