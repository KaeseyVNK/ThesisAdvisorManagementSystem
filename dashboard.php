<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('Vui lòng đăng nhập để tiếp tục', 'warning');
    redirect('login.php');
}

// Get user role and connection
$role = getCurrentUserRole();
$userDetails = getCurrentUserDetails();
$conn = getDBConnection();

// Get statistics based on user role
$stats = [];
if (isAdmin()) {
    // Get total students
    $stmt = $conn->query("SELECT COUNT(*) as total FROM SinhVien");
    $stats['students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total lecturers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM GiangVien");
    $stats['lecturers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total theses
    $stmt = $conn->query("SELECT COUNT(*) as total FROM DeTai");
    $stats['theses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total assignments
    $stmt = $conn->query("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan");
    $stats['assignments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} elseif (isFaculty()) {
    // Kiểm tra nếu không tìm thấy thông tin giảng viên
    if (!$userDetails) {
        // Hiển thị thông báo lỗi và vẫn hiển thị dashboard cơ bản
        $stats['students'] = 0;
        $stats['theses'] = 0;
        $stats['active'] = 0;
        $stats['completed'] = 0;
    } else {
        $facultyId = $userDetails['GiangVienID'];
        
        // Get total students assigned to this faculty
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId");
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        $stats['students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total theses assigned to this faculty
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT DeTaiID) as total FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId");
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        $stats['theses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total active assignments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId AND TrangThai = 'Đang hướng dẫn'");
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total completed assignments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId AND TrangThai = 'Đã hoàn thành'");
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
} elseif (isStudent()) {
    // Kiểm tra nếu không tìm thấy thông tin sinh viên
    if (!$userDetails) {
        // Hiển thị thông báo lỗi và vẫn hiển thị dashboard cơ bản
        $stats['advisor'] = false;
        $stats['thesis'] = false;
        $stats['progress'] = 0;
    } else {
        $studentId = $userDetails['SinhVienID'];
        
        // Get advisor info
        $stmt = $conn->prepare("
            SELECT gv.HoTen, gv.Email, svgv.NgayBatDau, svgv.TrangThai, svgv.ID as AssignmentID
            FROM SinhVienGiangVienHuongDan svgv
            JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE svgv.SinhVienID = :studentId
            ORDER BY svgv.NgayBatDau DESC
            LIMIT 1
        ");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $stats['advisor'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get thesis info
        $stmt = $conn->prepare("
            SELECT dt.TenDeTai, dt.LinhVuc, dt.TrangThai
            FROM SinhVienGiangVienHuongDan svgv
            JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
            WHERE svgv.SinhVienID = :studentId
            ORDER BY svgv.NgayBatDau DESC
            LIMIT 1
        ");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $stats['thesis'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get progress count - Sửa lỗi query
        $progressCount = 0;
        if (isset($stats['advisor']) && $stats['advisor']) {
            $assignmentId = $stats['advisor']['AssignmentID'];
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM TienDo 
                WHERE SinhVienGiangVienID = :assignmentId
            ");
            $stmt->bindParam(':assignmentId', $assignmentId);
            $stmt->execute();
            $progressCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        }
        $stats['progress'] = $progressCount;
    }
}

// Include header
$pageTitle = 'Bảng điều khiển';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Bảng điều khiển
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bảng điều khiển</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="avatar-container me-3">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div>
                    <h5 class="mb-1">Xin chào, <?php echo $_SESSION['username']; ?>!</h5>
                    <p class="text-muted mb-0">
                        <?php 
                        if (isAdmin()) echo 'Quản trị viên';
                        elseif (isFaculty()) echo 'Giảng viên';
                        else echo 'Sinh viên';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if (isAdmin()): ?>
        <!-- Admin Dashboard -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card overview-card overview-students">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h6 class="overview-title">Sinh viên</h6>
                        <h2 class="overview-value"><?php echo $stats['students']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-lecturers">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h6 class="overview-title">Giảng viên</h6>
                        <h2 class="overview-value"><?php echo $stats['lecturers']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-theses">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h6 class="overview-title">Đề tài</h6>
                        <h2 class="overview-value"><?php echo $stats['theses']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-assignments">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h6 class="overview-title">Phân công</h6>
                        <h2 class="overview-value"><?php echo $stats['assignments']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-graduate me-2 text-primary"></i>Quản lý sinh viên
                        </h5>
                        <a href="admin/students.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Quản lý thông tin sinh viên trong hệ thống. Thêm, sửa, xóa và xem chi tiết thông tin sinh viên.</p>
                        <div class="d-grid gap-2">
                            <a href="admin/add-student.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Thêm sinh viên mới
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Quản lý giảng viên
                        </h5>
                        <a href="admin/lecturers.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Quản lý thông tin giảng viên trong hệ thống. Thêm, sửa, xóa và xem chi tiết thông tin giảng viên.</p>
                        <div class="d-grid gap-2">
                            <a href="admin/add-lecturer.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Thêm giảng viên mới
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book me-2 text-primary"></i>Quản lý đề tài
                        </h5>
                        <a href="admin/theses.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Quản lý thông tin đề tài luận văn. Thêm, sửa, xóa và xem chi tiết thông tin đề tài.</p>
                        <div class="d-grid gap-2">
                            <a href="admin/add-thesis.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Thêm đề tài mới
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tasks me-2 text-primary"></i>Quản lý phân công
                        </h5>
                        <a href="admin/assignments.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Phân công giảng viên hướng dẫn đề tài. Quản lý việc phân công giảng viên cho từng đề tài.</p>
                        <div class="d-grid gap-2">
                            <a href="admin/assignments.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Tạo phân công mới
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif (isFaculty()): ?>
        <!-- Faculty Dashboard -->
        <?php if (!$userDetails): ?>
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-danger mb-0">
                        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Tài khoản chưa được thiết lập đầy đủ</h5>
                        <p>Tài khoản của bạn chưa được liên kết với thông tin giảng viên trong hệ thống. Vui lòng liên hệ quản trị viên để được hỗ trợ.</p>
                        <hr>
                        <p class="mb-0">Để quản trị viên cần thông tin sau để hỗ trợ:</p>
                        <ul>
                            <li>Tên đăng nhập: <strong><?php echo $_SESSION['username']; ?></strong></li>
                            <li>ID Người dùng: <strong><?php echo $_SESSION['user_id']; ?></strong></li>
                            <li>Email: <strong><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : 'Chưa cung cấp'; ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card overview-card overview-students">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h6 class="overview-title">Sinh viên hướng dẫn</h6>
                        <h2 class="overview-value"><?php echo $stats['students']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-theses">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h6 class="overview-title">Đề tài</h6>
                        <h2 class="overview-value"><?php echo $stats['theses']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-lecturers">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <h6 class="overview-title">Đang hướng dẫn</h6>
                        <h2 class="overview-value"><?php echo $stats['active']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card overview-card overview-assignments">
                    <div class="card-body">
                        <div class="overview-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h6 class="overview-title">Đã hoàn thành</h6>
                        <h2 class="overview-value"><?php echo $stats['completed']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-graduate me-2 text-primary"></i>Sinh viên hướng dẫn
                        </h5>
                        <a href="faculty/students.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Xem danh sách sinh viên đang được hướng dẫn. Theo dõi tiến độ và cung cấp phản hồi.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book me-2 text-primary"></i>Đề tài
                        </h5>
                        <a href="faculty/theses.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Quản lý đề tài đang hướng dẫn. Xem thông tin chi tiết và danh sách sinh viên thực hiện.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tasks me-2 text-primary"></i>Phân công
                        </h5>
                        <a href="faculty/assignments.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Truy cập
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Phân công sinh viên vào đề tài. Quản lý việc phân công sinh viên cho từng đề tài được hướng dẫn.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Student Dashboard -->
        <div class="row">
            <?php if (!$userDetails): ?>
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger mb-0">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Tài khoản chưa được thiết lập đầy đủ</h5>
                            <p>Tài khoản của bạn chưa được liên kết với thông tin sinh viên trong hệ thống. Vui lòng liên hệ quản trị viên để được hỗ trợ.</p>
                            <hr>
                            <p class="mb-0">Để quản trị viên cần thông tin sau để hỗ trợ:</p>
                            <ul>
                                <li>Tên đăng nhập: <strong><?php echo $_SESSION['username']; ?></strong></li>
                                <li>ID Người dùng: <strong><?php echo $_SESSION['user_id']; ?></strong></li>
                                <li>Email: <strong><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : 'Chưa cung cấp'; ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Giảng viên hướng dẫn
                        </h5>
                        <a href="student/advisor.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Chi tiết
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($stats['advisor']) && $stats['advisor']): ?>
                            <div class="advisor-info">
                                <h6 class="mb-3">Thông tin giảng viên hướng dẫn</h6>
                                <p><strong>Họ tên:</strong> <?php echo $stats['advisor']['HoTen']; ?></p>
                                <p><strong>Email:</strong> <?php echo $stats['advisor']['Email']; ?></p>
                                <p><strong>Ngày bắt đầu:</strong> <?php echo date('d/m/Y', strtotime($stats['advisor']['NgayBatDau'])); ?></p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-<?php echo $stats['advisor']['TrangThai'] == 'Đang hướng dẫn' ? 'primary' : ($stats['advisor']['TrangThai'] == 'Đã hoàn thành' ? 'success' : 'danger'); ?>">
                                        <?php echo $stats['advisor']['TrangThai']; ?>
                                    </span>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>Bạn chưa được phân công giảng viên hướng dẫn.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book me-2 text-primary"></i>Đề tài
                        </h5>
                        <a href="student/thesis.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right me-1"></i>Chi tiết
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($stats['thesis']) && $stats['thesis']): ?>
                            <div class="thesis-info">
                                <h6 class="mb-3">Thông tin đề tài</h6>
                                <p><strong>Tên đề tài:</strong> <?php echo $stats['thesis']['TenDeTai']; ?></p>
                                <p><strong>Lĩnh vực:</strong> <?php echo $stats['thesis']['LinhVuc']; ?></p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-<?php echo $stats['thesis']['TrangThai'] == 'Đã duyệt' ? 'success' : ($stats['thesis']['TrangThai'] == 'Chờ duyệt' ? 'warning' : 'danger'); ?>">
                                        <?php echo $stats['thesis']['TrangThai']; ?>
                                    </span>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>Bạn chưa được phân công đề tài.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tasks me-2 text-primary"></i>Tiến độ
                        </h5>
                        <div>
                            <a href="student/progress.php" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-list me-1"></i>Xem tiến độ
                            </a>
                            <a href="student/add-progress.php" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i>Thêm mới
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($stats['progress']) && $stats['progress'] > 0): ?>
                            <p class="mb-0">Bạn đã cập nhật <strong><?php echo $stats['progress']; ?></strong> báo cáo tiến độ. Tiếp tục cập nhật tiến độ thường xuyên để giảng viên theo dõi.</p>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa cập nhật tiến độ nào. Hãy cập nhật tiến độ thường xuyên để giảng viên theo dõi.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.avatar-circle {
    width: 60px;
    height: 60px;
    background-color: rgba(52, 152, 219, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--secondary-color);
}
</style>

<?php include 'includes/footer.php'; ?> 