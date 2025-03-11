<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $maSV = sanitizeInput($_POST['maSV']);
    $hoTen = sanitizeInput($_POST['hoTen']);
    $ngaySinh = sanitizeInput($_POST['ngaySinh']);
    $gioiTinh = sanitizeInput($_POST['gioiTinh']);
    $email = sanitizeInput($_POST['email']);
    $soDienThoai = sanitizeInput($_POST['soDienThoai']);
    $diaChi = sanitizeInput($_POST['diaChi']);
    $khoa = sanitizeInput($_POST['khoa']);
    $chuyenNganh = sanitizeInput($_POST['chuyenNganh']);
    $nienKhoa = sanitizeInput($_POST['nienKhoa']);
    $trangThai = sanitizeInput($_POST['trangThai']);
    $createAccount = isset($_POST['createAccount']) ? true : false;
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($maSV)) {
        $errors[] = 'Mã sinh viên không được để trống';
    }
    
    if (empty($hoTen)) {
        $errors[] = 'Họ tên không được để trống';
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if ($createAccount) {
        if (empty($username)) {
            $errors[] = 'Tên đăng nhập không được để trống';
        }
        
        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
    }
    
    // If no errors, add student to database
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Check if student with the same MaSV already exists
            $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE MaSV = :maSV");
            $stmt->bindParam(':maSV', $maSV);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Mã sinh viên đã tồn tại';
            } else {
                // Begin transaction
                $conn->beginTransaction();
                
                $userId = null;
                
                // Create user account if requested
                if ($createAccount) {
                    // Check if username already exists
                    $stmt = $conn->prepare("SELECT * FROM Users WHERE Username = :username");
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $errors[] = 'Tên đăng nhập đã tồn tại';
                        throw new Exception('Tên đăng nhập đã tồn tại');
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into Users table
                    $stmt = $conn->prepare("
                        INSERT INTO Users (Username, Password, Email, Role)
                        VALUES (:username, :password, :email, 'student')
                    ");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    $userId = $conn->lastInsertId();
                }
                
                // Insert into SinhVien table
                $stmt = $conn->prepare("
                    INSERT INTO SinhVien (UserID, MaSV, HoTen, NgaySinh, GioiTinh, Email, SoDienThoai, DiaChi, Khoa, ChuyenNganh, NienKhoa, TrangThai)
                    VALUES (:userId, :maSV, :hoTen, :ngaySinh, :gioiTinh, :email, :soDienThoai, :diaChi, :khoa, :chuyenNganh, :nienKhoa, :trangThai)
                ");
                $stmt->bindParam(':userId', $userId);
                $stmt->bindParam(':maSV', $maSV);
                $stmt->bindParam(':hoTen', $hoTen);
                $stmt->bindParam(':ngaySinh', $ngaySinh);
                $stmt->bindParam(':gioiTinh', $gioiTinh);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':soDienThoai', $soDienThoai);
                $stmt->bindParam(':diaChi', $diaChi);
                $stmt->bindParam(':khoa', $khoa);
                $stmt->bindParam(':chuyenNganh', $chuyenNganh);
                $stmt->bindParam(':nienKhoa', $nienKhoa);
                $stmt->bindParam(':trangThai', $trangThai);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                setFlashMessage('Thêm sinh viên thành công', 'success');
                redirect('../admin/students.php');
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Include header
$pageTitle = 'Thêm sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="students.php">Quản lý sinh viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Thêm sinh viên</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-user-plus me-2"></i>Thêm sinh viên
                </h2>
                <p class="card-text">Thêm thông tin sinh viên mới vào hệ thống.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin cá nhân</h4>
                            <div class="mb-3">
                                <label for="maSV" class="form-label">Mã sinh viên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="maSV" name="maSV" value="<?php echo isset($_POST['maSV']) ? htmlspecialchars($_POST['maSV']) : ''; ?>" required>
                                <div class="invalid-feedback">Vui lòng nhập mã sinh viên</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="hoTen" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo isset($_POST['hoTen']) ? htmlspecialchars($_POST['hoTen']) : ''; ?>" required>
                                <div class="invalid-feedback">Vui lòng nhập họ tên</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ngaySinh" class="form-label">Ngày sinh</label>
                                <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" value="<?php echo isset($_POST['ngaySinh']) ? htmlspecialchars($_POST['ngaySinh']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="gioiTinh" class="form-label">Giới tính</label>
                                <select class="form-select" id="gioiTinh" name="gioiTinh">
                                    <option value="Nam" <?php echo (isset($_POST['gioiTinh']) && $_POST['gioiTinh'] === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                                    <option value="Nữ" <?php echo (isset($_POST['gioiTinh']) && $_POST['gioiTinh'] === 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                                    <option value="Khác" <?php echo (isset($_POST['gioiTinh']) && $_POST['gioiTinh'] === 'Khác') ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="soDienThoai" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo isset($_POST['soDienThoai']) ? htmlspecialchars($_POST['soDienThoai']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="diaChi" class="form-label">Địa chỉ</label>
                                <textarea class="form-control" id="diaChi" name="diaChi" rows="3"><?php echo isset($_POST['diaChi']) ? htmlspecialchars($_POST['diaChi']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin học tập</h4>
                            <div class="mb-3">
                                <label for="khoa" class="form-label">Khoa</label>
                                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo isset($_POST['khoa']) ? htmlspecialchars($_POST['khoa']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="chuyenNganh" class="form-label">Chuyên ngành</label>
                                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh" value="<?php echo isset($_POST['chuyenNganh']) ? htmlspecialchars($_POST['chuyenNganh']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="nienKhoa" class="form-label">Niên khóa</label>
                                <input type="text" class="form-control" id="nienKhoa" name="nienKhoa" value="<?php echo isset($_POST['nienKhoa']) ? htmlspecialchars($_POST['nienKhoa']) : ''; ?>" placeholder="VD: 2020-2024">
                            </div>
                            
                            <div class="mb-3">
                                <label for="trangThai" class="form-label">Trạng thái</label>
                                <select class="form-select" id="trangThai" name="trangThai">
                                    <option value="Đang học" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Đang học') ? 'selected' : ''; ?>>Đang học</option>
                                    <option value="Đã tốt nghiệp" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Đã tốt nghiệp') ? 'selected' : ''; ?>>Đã tốt nghiệp</option>
                                    <option value="Đã nghỉ học" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Đã nghỉ học') ? 'selected' : ''; ?>>Đã nghỉ học</option>
                                </select>
                            </div>
                            
                            <h4 class="mb-3 mt-4">Tài khoản đăng nhập</h4>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="createAccount" name="createAccount" <?php echo isset($_POST['createAccount']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="createAccount">Tạo tài khoản đăng nhập</label>
                            </div>
                            
                            <div id="accountFields" class="<?php echo isset($_POST['createAccount']) ? '' : 'd-none'; ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    <div class="invalid-feedback">Vui lòng nhập tên đăng nhập</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <span class="input-group-text">
                                            <i class="fas fa-eye toggle-password" toggle="#password"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback">Vui lòng nhập mật khẩu (ít nhất 6 ký tự)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lưu
                        </button>
                        <a href="students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle account fields visibility
    var createAccountCheckbox = document.getElementById('createAccount');
    var accountFields = document.getElementById('accountFields');
    
    createAccountCheckbox.addEventListener('change', function() {
        if (this.checked) {
            accountFields.classList.remove('d-none');
            document.getElementById('username').setAttribute('required', '');
            document.getElementById('password').setAttribute('required', '');
        } else {
            accountFields.classList.add('d-none');
            document.getElementById('username').removeAttribute('required');
            document.getElementById('password').removeAttribute('required');
        }
    });
    
    // Auto-generate username from maSV
    var maSVInput = document.getElementById('maSV');
    var usernameInput = document.getElementById('username');
    
    maSVInput.addEventListener('blur', function() {
        if (usernameInput.value === '') {
            usernameInput.value = this.value.toLowerCase();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 