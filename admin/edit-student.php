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
$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $maSV = trim($_POST['maSV']);
        $hoTen = trim($_POST['hoTen']);
        $gioiTinh = trim($_POST['gioiTinh']);
        $email = trim($_POST['email']);
        $soDienThoai = trim($_POST['soDienThoai']);
        $khoa = trim($_POST['khoa']);
        $chuyenNganh = trim($_POST['chuyenNganh']);
        $nienKhoa = trim($_POST['nienKhoa']);
        $trangThai = trim($_POST['trangThai']);

        // Basic validation
        if (empty($maSV) || empty($hoTen) || empty($email)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }

        // Check if MaSV already exists for different student
        $stmt = $conn->prepare("SELECT SinhVienID FROM SinhVien WHERE MaSV = :maSV AND SinhVienID != :id");
        $stmt->bindParam(':maSV', $maSV);
        $stmt->bindParam(':id', $studentId);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            throw new Exception('Mã sinh viên đã tồn tại');
        }

        // Update student
        $stmt = $conn->prepare("
            UPDATE SinhVien SET 
            MaSV = :maSV,
            HoTen = :hoTen,
            GioiTinh = :gioiTinh,
            Email = :email,
            SoDienThoai = :soDienThoai,
            Khoa = :khoa,
            ChuyenNganh = :chuyenNganh,
            NienKhoa = :nienKhoa,
            TrangThai = :trangThai
            WHERE SinhVienID = :id
        ");

        $stmt->bindParam(':maSV', $maSV);
        $stmt->bindParam(':hoTen', $hoTen);
        $stmt->bindParam(':gioiTinh', $gioiTinh);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':soDienThoai', $soDienThoai);
        $stmt->bindParam(':khoa', $khoa);
        $stmt->bindParam(':chuyenNganh', $chuyenNganh);
        $stmt->bindParam(':nienKhoa', $nienKhoa);
        $stmt->bindParam(':trangThai', $trangThai);
        $stmt->bindParam(':id', $studentId);

        $stmt->execute();

        setFlashMessage('Cập nhật thông tin sinh viên thành công', 'success');
        redirect("view-student.php?id=$studentId");

    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Get student data
try {
    $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE SinhVienID = :id");
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy sinh viên', 'danger');
        redirect('students.php');
    }
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('students.php');
}

// Include header
$pageTitle = 'Chỉnh sửa sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="students.php">Quản lý sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa sinh viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Chỉnh sửa thông tin sinh viên</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maSV" class="form-label">Mã sinh viên *</label>
                                <input type="text" class="form-control" id="maSV" name="maSV" value="<?php echo htmlspecialchars($student['MaSV']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="hoTen" class="form-label">Họ và tên *</label>
                                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo htmlspecialchars($student['HoTen']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="gioiTinh" class="form-label">Giới tính</label>
                                <select class="form-select" id="gioiTinh" name="gioiTinh">
                                    <option value="Nam" <?php echo $student['GioiTinh'] == 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                    <option value="Nữ" <?php echo $student['GioiTinh'] == 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['Email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="soDienThoai" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo htmlspecialchars($student['SoDienThoai']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="khoa" class="form-label">Khoa</label>
                                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo htmlspecialchars($student['Khoa']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="chuyenNganh" class="form-label">Chuyên ngành</label>
                                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh" value="<?php echo htmlspecialchars($student['ChuyenNganh']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="nienKhoa" class="form-label">Niên khóa</label>
                                <input type="text" class="form-control" id="nienKhoa" name="nienKhoa" value="<?php echo htmlspecialchars($student['NienKhoa']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="trangThai" class="form-label">Trạng thái</label>
                                <select class="form-select" id="trangThai" name="trangThai">
                                    <option value="Đang học" <?php echo $student['TrangThai'] == 'Đang học' ? 'selected' : ''; ?>>Đang học</option>
                                    <option value="Đã tốt nghiệp" <?php echo $student['TrangThai'] == 'Đã tốt nghiệp' ? 'selected' : ''; ?>>Đã tốt nghiệp</option>
                                    <option value="Nghỉ học" <?php echo $student['TrangThai'] == 'Nghỉ học' ? 'selected' : ''; ?>>Nghỉ học</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="students.php" class="btn btn-secondary me-2">Hủy</a>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>