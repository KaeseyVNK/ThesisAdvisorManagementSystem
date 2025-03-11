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
    $maGV = sanitizeInput($_POST['maGV']);
    $hoTen = sanitizeInput($_POST['hoTen']);
    $hocVi = sanitizeInput($_POST['hocVi']);
    $chucVu = sanitizeInput($_POST['chucVu']);
    $email = sanitizeInput($_POST['email']);
    $soDienThoai = sanitizeInput($_POST['soDienThoai']);
    $khoa = sanitizeInput($_POST['khoa']);
    $chuyenNganh = sanitizeInput($_POST['chuyenNganh']);
    $soLuongSinhVienToiDa = sanitizeInput($_POST['soLuongSinhVienToiDa']);
    $createAccount = isset($_POST['createAccount']) ? true : false;
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($maGV)) {
        $errors[] = 'Mã giảng viên không được để trống';
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
    
    // If no errors, add lecturer to database
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Check if lecturer with the same MaGV already exists
            $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE MaGV = :maGV");
            $stmt->bindParam(':maGV', $maGV);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Mã giảng viên đã tồn tại';
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
                        throw new Exception('Tên đăng nhập đã tồn tại');
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into Users table
                    $stmt = $conn->prepare("
                        INSERT INTO Users (Username, Password, Email, Role)
                        VALUES (:username, :password, :email, 'faculty')
                    ");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    $userId = $conn->lastInsertId();
                }
                
                // Insert into GiangVien table
                $stmt = $conn->prepare("
                    INSERT INTO GiangVien (UserID, MaGV, HoTen, HocVi, ChucVu, Email, SoDienThoai, Khoa, ChuyenNganh, SoLuongSinhVienToiDa)
                    VALUES (:userId, :maGV, :hoTen, :hocVi, :chucVu, :email, :soDienThoai, :khoa, :chuyenNganh, :soLuongSinhVienToiDa)
                ");
                
                $stmt->bindParam(':userId', $userId);
                $stmt->bindParam(':maGV', $maGV);
                $stmt->bindParam(':hoTen', $hoTen);
                $stmt->bindParam(':hocVi', $hocVi);
                $stmt->bindParam(':chucVu', $chucVu);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':soDienThoai', $soDienThoai);
                $stmt->bindParam(':khoa', $khoa);
                $stmt->bindParam(':chuyenNganh', $chuyenNganh);
                $stmt->bindParam(':soLuongSinhVienToiDa', $soLuongSinhVienToiDa);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                setFlashMessage('Thêm giảng viên thành công', 'success');
                redirect('lecturers.php');
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
$pageTitle = 'Thêm giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="lecturers.php">Quản lý giảng viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Thêm giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thêm giảng viên mới</h5>
            </div>
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

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <div class="mb-3">
                                <label for="maGV" class="form-label">Mã giảng viên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="maGV" name="maGV" value="<?php echo isset($_POST['maGV']) ? htmlspecialchars($_POST['maGV']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="hoTen" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo isset($_POST['hoTen']) ? htmlspecialchars($_POST['hoTen']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="soDienThoai" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo isset($_POST['soDienThoai']) ? htmlspecialchars($_POST['soDienThoai']) : ''; ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin học thuật</h5>

                            <div class="mb-3">
                                <label for="hocVi" class="form-label">Học vị</label>
                                <select class="form-select" id="hocVi" name="hocVi">
                                    <option value="Thạc sĩ" <?php echo (isset($_POST['hocVi']) && $_POST['hocVi'] === 'Thạc sĩ') ? 'selected' : ''; ?>>Thạc sĩ</option>
                                    <option value="Tiến sĩ" <?php echo (isset($_POST['hocVi']) && $_POST['hocVi'] === 'Tiến sĩ') ? 'selected' : ''; ?>>Tiến sĩ</option>
                                    <option value="Phó Giáo sư" <?php echo (isset($_POST['hocVi']) && $_POST['hocVi'] === 'Phó Giáo sư') ? 'selected' : ''; ?>>Phó Giáo sư</option>
                                    <option value="Giáo sư" <?php echo (isset($_POST['hocVi']) && $_POST['hocVi'] === 'Giáo sư') ? 'selected' : ''; ?>>Giáo sư</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="chucVu" class="form-label">Chức vụ</label>
                                <input type="text" class="form-control" id="chucVu" name="chucVu" value="<?php echo isset($_POST['chucVu']) ? htmlspecialchars($_POST['chucVu']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="khoa" class="form-label">Khoa</label>
                                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo isset($_POST['khoa']) ? htmlspecialchars($_POST['khoa']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="chuyenNganh" class="form-label">Chuyên ngành</label>
                                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh" value="<?php echo isset($_POST['chuyenNganh']) ? htmlspecialchars($_POST['chuyenNganh']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="soLuongSinhVienToiDa" class="form-label">Số lượng sinh viên tối đa</label>
                                <input type="number" class="form-control" id="soLuongSinhVienToiDa" name="soLuongSinhVienToiDa" value="<?php echo isset($_POST['soLuongSinhVienToiDa']) ? htmlspecialchars($_POST['soLuongSinhVienToiDa']) : '10'; ?>" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3">Tài khoản đăng nhập</h5>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="createAccount" name="createAccount" <?php echo isset($_POST['createAccount']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="createAccount">Tạo tài khoản đăng nhập</label>
                            </div>

                            <div id="accountFields" class="<?php echo isset($_POST['createAccount']) ? '' : 'd-none'; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lưu
                        </button>
                        <a href="lecturers.php" class="btn btn-secondary">
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
    const createAccountCheckbox = document.getElementById('createAccount');
    const accountFields = document.getElementById('accountFields');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    createAccountCheckbox.addEventListener('change', function() {
        if (this.checked) {
            accountFields.classList.remove('d-none');
            usernameInput.setAttribute('required', '');
            passwordInput.setAttribute('required', '');
        } else {
            accountFields.classList.add('d-none');
            usernameInput.removeAttribute('required');
            passwordInput.removeAttribute('required');
        }
    });

    // Auto-generate username from maGV
    const maGVInput = document.getElementById('maGV');
    
    maGVInput.addEventListener('blur', function() {
        if (usernameInput.value === '') {
            usernameInput.value = this.value.toLowerCase();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 