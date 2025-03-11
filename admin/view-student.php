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
    setFlashMessage('ID sinh viên không hợp lệ', 'danger');
    redirect('students.php');
}

$studentId = $_GET['id'];

try {
    $conn = getDBConnection();
    
    // Get student details with user information
    $stmt = $conn->prepare("
        SELECT sv.*, u.Username, u.Email as UserEmail,
        (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE SinhVienID = sv.SinhVienID) as AssignmentCount
        FROM SinhVien sv
        LEFT JOIN Users u ON sv.UserID = u.UserID
        WHERE sv.SinhVienID = :id
    ");
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy sinh viên', 'danger');
        redirect('students.php');
    }
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get advisor assignments
    $stmt = $conn->prepare("
        SELECT gv.HoTen as TenGiangVien, gv.Email as EmailGiangVien
        FROM SinhVienGiangVienHuongDan svgv
        JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
        WHERE svgv.SinhVienID = :id
    ");
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('students.php');
}

// Include header
$pageTitle = 'Chi tiết sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="students.php">Quản lý sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chi tiết sinh viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Thông tin sinh viên</h5>
                <div>
                    <a href="edit-student.php?id=<?php echo $studentId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </a>
                    <a href="students.php" class="btn btn-secondary">
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
                                <th width="30%">Mã sinh viên:</th>
                                <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                            </tr>
                            <tr>
                                <th>Họ và tên:</th>
                                <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                            </tr>
                            <tr>
                                <th>Giới tính:</th>
                                <td><?php echo htmlspecialchars($student['GioiTinh']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($student['Email']); ?></td>
                            </tr>
                            <tr>
                                <th>Số điện thoại:</th>
                                <td><?php echo htmlspecialchars($student['SoDienThoai']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Thông tin học tập</h6>
                        <table class="table">
                            <tr>
                                <th width="30%">Khoa:</th>
                                <td><?php echo htmlspecialchars($student['Khoa']); ?></td>
                            </tr>
                            <tr>
                                <th>Chuyên ngành:</th>
                                <td><?php echo htmlspecialchars($student['ChuyenNganh']); ?></td>
                            </tr>
                            <tr>
                                <th>Niên khóa:</th>
                                <td><?php echo htmlspecialchars($student['NienKhoa']); ?></td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    <?php if ($student['TrangThai'] == 'Đang học'): ?>
                                        <span class="badge bg-primary"><?php echo $student['TrangThai']; ?></span>
                                    <?php elseif ($student['TrangThai'] == 'Đã tốt nghiệp'): ?>
                                        <span class="badge bg-success"><?php echo $student['TrangThai']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?php echo $student['TrangThai']; ?></span>
                                    <?php endif; ?>
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
                                    <?php if ($student['UserID']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i><?php echo htmlspecialchars($student['Username']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-times me-1"></i>Chưa có tài khoản
                                        </span>
                                        <a href="create-account.php?id=<?php echo $studentId; ?>&type=student" class="btn btn-sm btn-success ms-2">
                                            <i class="fas fa-user-plus me-1"></i>Tạo tài khoản
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($advisors)): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="fw-bold">Giảng viên hướng dẫn</h6>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advisors as $advisor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($advisor['TenGiangVien']); ?></td>
                                    <td><?php echo htmlspecialchars($advisor['EmailGiangVien']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>