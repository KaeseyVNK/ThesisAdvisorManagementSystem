<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('Vui lòng đăng nhập để tiếp tục', 'warning');
    redirect('login.php');
}

// Get user role and details
$role = getCurrentUserRole();
$userDetails = getCurrentUserDetails();
$userId = getCurrentUserId();
$conn = getDBConnection();

// Get user account information
$stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = :userId");
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$userAccount = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission for updating profile
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $conn->beginTransaction();
        
        // Update user account information
        $email = sanitizeInput($_POST['email']);
        
        $stmt = $conn->prepare("UPDATE Users SET Email = :email WHERE UserID = :userId");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        
        // Update role-specific information
        if (isStudent() && $userDetails) {
            $phone = sanitizeInput($_POST['phone']);
            $address = sanitizeInput($_POST['address']);
            
            $stmt = $conn->prepare("UPDATE SinhVien SET SoDienThoai = :phone, DiaChi = :address WHERE SinhVienID = :studentId");
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':studentId', $userDetails['SinhVienID']);
            $stmt->execute();
        } elseif (isFaculty() && $userDetails) {
            $phone = sanitizeInput($_POST['phone']);
            $researchInterests = sanitizeInput($_POST['research_interests']);
            
            $stmt = $conn->prepare("UPDATE GiangVien SET SoDienThoai = :phone, LinhVucNghienCuu = :researchInterests WHERE GiangVienID = :facultyId");
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':researchInterests', $researchInterests);
            $stmt->bindParam(':facultyId', $userDetails['GiangVienID']);
            $stmt->execute();
        }
        
        $conn->commit();
        $success = true;
        setFlashMessage('Cập nhật thông tin thành công!', 'success');
        
        // Refresh user details
        $userDetails = getCurrentUserDetails();
        $stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = :userId");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $userAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = 'Lỗi: ' . $e->getMessage();
    }
}

// Include header
$pageTitle = 'Hồ sơ cá nhân';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hồ sơ cá nhân</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>Thông tin đã được cập nhật thành công!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $errors[0]; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card profile-card">
                <div class="card-body text-center">
                    <div class="profile-image-container mb-4">
                        <div class="profile-image">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($userAccount['Username']); ?></h4>
                    <p class="text-muted mb-3">
                        <?php 
                        if (isAdmin()) echo 'Quản trị viên';
                        elseif (isFaculty()) echo 'Giảng viên';
                        else echo 'Sinh viên';
                        ?>
                    </p>
                    
                    <?php if (isStudent() && $userDetails): ?>
                    <div class="profile-info">
                        <div class="profile-info-item">
                            <i class="fas fa-id-card"></i>
                            <span><?php echo htmlspecialchars($userDetails['MaSV']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo htmlspecialchars($userDetails['ChuyenNganh']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-university"></i>
                            <span><?php echo htmlspecialchars($userDetails['Khoa']); ?></span>
                        </div>
                    </div>
                    <?php elseif (isFaculty() && $userDetails): ?>
                    <div class="profile-info">
                        <div class="profile-info-item">
                            <i class="fas fa-id-card"></i>
                            <span><?php echo htmlspecialchars($userDetails['MaGV']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo htmlspecialchars($userDetails['ChuyenNganh']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-university"></i>
                            <span><?php echo htmlspecialchars($userDetails['Khoa']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="change-password.php" class="btn btn-outline-primary">
                            <i class="fas fa-key me-2"></i>Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cá nhân
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="false">
                                <i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Thông tin cá nhân -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <?php if (!$userDetails && !isAdmin()): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Tài khoản của bạn chưa được liên kết với thông tin trong hệ thống. Vui lòng liên hệ quản trị viên để được hỗ trợ.
                            </div>
                            <?php else: ?>
                            
                            <h5 class="card-title mb-4">Thông tin tài khoản</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($userAccount['Username']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($userAccount['Email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Vai trò:</strong> 
                                        <span class="badge bg-<?php echo isAdmin() ? 'danger' : (isFaculty() ? 'primary' : 'success'); ?>">
                                            <?php 
                                            if (isAdmin()) echo 'Quản trị viên';
                                            elseif (isFaculty()) echo 'Giảng viên';
                                            else echo 'Sinh viên';
                                            ?>
                                        </span>
                                    </p>
                                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y', strtotime($userAccount['CreatedAt'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if (isStudent() && $userDetails): ?>
                            <h5 class="card-title mb-4">Thông tin sinh viên</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($userDetails['MaSV']); ?></p>
                                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($userDetails['HoTen']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($userDetails['Email']); ?></p>
                                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($userDetails['SoDienThoai'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Khoa:</strong> <?php echo htmlspecialchars($userDetails['Khoa']); ?></p>
                                    <p><strong>Chuyên ngành:</strong> <?php echo htmlspecialchars($userDetails['ChuyenNganh']); ?></p>
                                    <p><strong>Niên khóa:</strong> <?php echo htmlspecialchars($userDetails['NienKhoa']); ?></p>
                                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($userDetails['DiaChi'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                            </div>
                            <?php elseif (isFaculty() && $userDetails): ?>
                            <h5 class="card-title mb-4">Thông tin giảng viên</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Mã giảng viên:</strong> <?php echo htmlspecialchars($userDetails['MaGV']); ?></p>
                                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($userDetails['HoTen']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($userDetails['Email']); ?></p>
                                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($userDetails['SoDienThoai'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Khoa:</strong> <?php echo htmlspecialchars($userDetails['Khoa']); ?></p>
                                    <p><strong>Chuyên ngành:</strong> <?php echo htmlspecialchars($userDetails['ChuyenNganh']); ?></p>
                                    <p><strong>Học hàm/Học vị:</strong> <?php echo htmlspecialchars($userDetails['HocHamHocVi'] ?? 'Chưa cập nhật'); ?></p>
                                    <p><strong>Lĩnh vực nghiên cứu:</strong> <?php echo htmlspecialchars($userDetails['LinhVucNghienCuu'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Chỉnh sửa thông tin -->
                        <div class="tab-pane fade" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                            <?php if (!$userDetails && !isAdmin()): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Tài khoản của bạn chưa được liên kết với thông tin trong hệ thống. Vui lòng liên hệ quản trị viên để được hỗ trợ.
                            </div>
                            <?php else: ?>
                            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <h5 class="card-title mb-4">Cập nhật thông tin tài khoản</h5>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Tên đăng nhập</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($userAccount['Username']); ?>" readonly disabled>
                                    <div class="form-text">Tên đăng nhập không thể thay đổi.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userAccount['Email']); ?>" required>
                                </div>
                                
                                <?php if (isStudent() && $userDetails): ?>
                                <h5 class="card-title mb-4 mt-4">Cập nhật thông tin sinh viên</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fullname" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="fullname" value="<?php echo htmlspecialchars($userDetails['HoTen']); ?>" readonly disabled>
                                        <div class="form-text">Họ tên không thể thay đổi.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="student_id" class="form-label">Mã sinh viên</label>
                                        <input type="text" class="form-control" id="student_id" value="<?php echo htmlspecialchars($userDetails['MaSV']); ?>" readonly disabled>
                                        <div class="form-text">Mã sinh viên không thể thay đổi.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userDetails['SoDienThoai'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Địa chỉ</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($userDetails['DiaChi'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <?php elseif (isFaculty() && $userDetails): ?>
                                <h5 class="card-title mb-4 mt-4">Cập nhật thông tin giảng viên</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fullname" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="fullname" value="<?php echo htmlspecialchars($userDetails['HoTen']); ?>" readonly disabled>
                                        <div class="form-text">Họ tên không thể thay đổi.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="faculty_id" class="form-label">Mã giảng viên</label>
                                        <input type="text" class="form-control" id="faculty_id" value="<?php echo htmlspecialchars($userDetails['MaGV']); ?>" readonly disabled>
                                        <div class="form-text">Mã giảng viên không thể thay đổi.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userDetails['SoDienThoai'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="research_interests" class="form-label">Lĩnh vực nghiên cứu</label>
                                        <input type="text" class="form-control" id="research_interests" name="research_interests" value="<?php echo htmlspecialchars($userDetails['LinhVucNghienCuu'] ?? ''); ?>">
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Lưu thay đổi
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.profile-image-container {
    position: relative;
    margin: 0 auto;
}

.profile-image {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    margin: 0 auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.profile-info {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.profile-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 0 20px;
}

.profile-info-item i {
    width: 30px;
    height: 30px;
    background-color: rgba(52, 152, 219, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    margin-right: 15px;
}

.nav-tabs .nav-link {
    color: var(--gray-color);
    border: none;
    padding: 10px 20px;
    border-radius: 0;
    position: relative;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    background-color: transparent;
    font-weight: 600;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    border-radius: 3px 3px 0 0;
}

.nav-tabs .nav-link:hover {
    color: var(--primary-color);
}

.card-title {
    color: var(--primary-color);
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

.card-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    border-radius: 3px;
}
</style>

<?php include 'includes/footer.php'; ?> 