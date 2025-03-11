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
$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $maGV = trim($_POST['maGV']);
        $hoTen = trim($_POST['hoTen']);
        $hocVi = trim($_POST['hocVi']);
        $chucVu = trim($_POST['chucVu']);
        $email = trim($_POST['email']);
        $soDienThoai = trim($_POST['soDienThoai']);
        $khoa = trim($_POST['khoa']);
        $chuyenNganh = trim($_POST['chuyenNganh']);
        $soLuongSinhVienToiDa = trim($_POST['soLuongSinhVienToiDa']);

        // Basic validation
        if (empty($maGV) || empty($hoTen) || empty($email)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }

        // Check if MaGV already exists for different lecturer
        $stmt = $conn->prepare("SELECT GiangVienID FROM GiangVien WHERE MaGV = :maGV AND GiangVienID != :id");
        $stmt->bindParam(':maGV', $maGV);
        $stmt->bindParam(':id', $lecturerId);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            throw new Exception('Mã giảng viên đã tồn tại');
        }

        // Update lecturer
        $stmt = $conn->prepare("
            UPDATE GiangVien SET 
            MaGV = :maGV,
            HoTen = :hoTen,
            HocVi = :hocVi,
            ChucVu = :chucVu,
            Email = :email,
            SoDienThoai = :soDienThoai,
            Khoa = :khoa,
            ChuyenNganh = :chuyenNganh,
            SoLuongSinhVienToiDa = :soLuongSinhVienToiDa
            WHERE GiangVienID = :id
        ");

        $stmt->bindParam(':maGV', $maGV);
        $stmt->bindParam(':hoTen', $hoTen);
        $stmt->bindParam(':hocVi', $hocVi);
        $stmt->bindParam(':chucVu', $chucVu);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':soDienThoai', $soDienThoai);
        $stmt->bindParam(':khoa', $khoa);
        $stmt->bindParam(':chuyenNganh', $chuyenNganh);
        $stmt->bindParam(':soLuongSinhVienToiDa', $soLuongSinhVienToiDa);
        $stmt->bindParam(':id', $lecturerId);

        $stmt->execute();

        setFlashMessage('Cập nhật thông tin giảng viên thành công', 'success');
        redirect("view-lecturer.php?id=$lecturerId");

    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Get lecturer data
try {
    $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE GiangVienID = :id");
    $stmt->bindParam(':id', $lecturerId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy giảng viên', 'danger');
        redirect('lecturers.php');
    }
    
    $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('lecturers.php');
}

// Include header
$pageTitle = 'Chỉnh sửa giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="lecturers.php">Quản lý giảng viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Chỉnh sửa thông tin giảng viên</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maGV" class="form-label">Mã giảng viên *</label>
                                <input type="text" class="form-control" id="maGV" name="maGV" value="<?php echo htmlspecialchars($lecturer['MaGV']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="hoTen" class="form-label">Họ và tên *</label>
                                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo htmlspecialchars($lecturer['HoTen']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($lecturer['Email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="soDienThoai" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo htmlspecialchars($lecturer['SoDienThoai']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hocVi" class="form-label">Học vị</label>
                                <select class="form-select" id="hocVi" name="hocVi">
                                    <option value="Thạc sĩ" <?php echo $lecturer['HocVi'] == 'Thạc sĩ' ? 'selected' : ''; ?>>Thạc sĩ</option>
                                    <option value="Tiến sĩ" <?php echo $lecturer['HocVi'] == 'Tiến sĩ' ? 'selected' : ''; ?>>Tiến sĩ</option>
                                    <option value="Phó Giáo sư" <?php echo $lecturer['HocVi'] == 'Phó Giáo sư' ? 'selected' : ''; ?>>Phó Giáo sư</option>
                                    <option value="Giáo sư" <?php echo $lecturer['HocVi'] == 'Giáo sư' ? 'selected' : ''; ?>>Giáo sư</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="chucVu" class="form-label">Chức vụ</label>
                                <input type="text" class="form-control" id="chucVu" name="chucVu" value="<?php echo htmlspecialchars($lecturer['ChucVu']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="khoa" class="form-label">Khoa</label>
                                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo htmlspecialchars($lecturer['Khoa']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="chuyenNganh" class="form-label">Chuyên ngành</label>
                                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh" value="<?php echo htmlspecialchars($lecturer['ChuyenNganh']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="soLuongSinhVienToiDa" class="form-label">Số lượng sinh viên tối đa</label>
                                <input type="number" class="form-control" id="soLuongSinhVienToiDa" name="soLuongSinhVienToiDa" value="<?php echo htmlspecialchars($lecturer['SoLuongSinhVienToiDa']); ?>" min="1">
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="lecturers.php" class="btn btn-secondary me-2">Hủy</a>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 