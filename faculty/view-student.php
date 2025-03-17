<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a faculty member
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get student ID from URL
$studentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$studentId) {
    setFlashMessage('Không tìm thấy thông tin sinh viên', 'danger');
    redirect('students.php');
}

// Get faculty details
$facultyDetails = getCurrentUserDetails();
$facultyId = $facultyDetails['GiangVienID'];

// Get student details
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT 
        sv.*,
        svgv.ID as AssignmentID,
        svgv.NgayBatDau,
        svgv.TrangThai as AssignmentStatus,
        dt.TenDeTai,
        dt.MoTa as MoTaDeTai,
        dt.LinhVuc
    FROM SinhVien sv
    JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE sv.SinhVienID = :studentId
    AND svgv.GiangVienID = :facultyId
");
$stmt->bindParam(':studentId', $studentId);
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    setFlashMessage('Không tìm thấy thông tin sinh viên', 'danger');
    redirect('students.php');
}

// Get student's progress
$stmt = $conn->prepare("
    SELECT *
    FROM TienDo
    WHERE SinhVienGiangVienID = :assignmentId
    ORDER BY NgayCapNhat DESC
");
$stmt->bindParam(':assignmentId', $student['AssignmentID']);
$stmt->execute();
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Chi tiết sinh viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="students.php">Sinh viên hướng dẫn</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chi tiết sinh viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin sinh viên</h5>
            </div>
            <div class="card-body">
                <p><strong>Mã SV:</strong> <?php echo $student['MaSV']; ?></p>
                <p><strong>Họ tên:</strong> <?php echo $student['HoTen']; ?></p>
                <p><strong>Khoa:</strong> <?php echo $student['Khoa']; ?></p>
                <p><strong>Chuyên ngành:</strong> <?php echo $student['ChuyenNganh']; ?></p>
                <p><strong>Email:</strong> <?php echo $student['Email']; ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo $student['SoDienThoai']; ?></p>
                <p><strong>Ngày bắt đầu:</strong> <?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></p>
                <p><strong>Trạng thái:</strong> 
                    <?php if ($student['AssignmentStatus'] == 'Đang hướng dẫn'): ?>
                        <span class="badge bg-primary"><?php echo $student['AssignmentStatus']; ?></span>
                    <?php elseif ($student['AssignmentStatus'] == 'Đã hoàn thành'): ?>
                        <span class="badge bg-success"><?php echo $student['AssignmentStatus']; ?></span>
                    <?php else: ?>
                        <span class="badge bg-danger"><?php echo $student['AssignmentStatus']; ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <?php if ($student['TenDeTai']): ?>
                    <p><strong>Tên đề tài:</strong> <?php echo $student['TenDeTai']; ?></p>
                    <p><strong>Lĩnh vực:</strong> <?php echo $student['LinhVuc']; ?></p>
                    <p><strong>Mô tả:</strong> <?php echo $student['MoTaDeTai']; ?></p>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Sinh viên chưa được phân công đề tài.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Tiến độ</h5>
                <a href="progress.php?student_id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-tasks me-1"></i>Xem tất cả
                </a>
            </div>
            <div class="card-body">
                <?php if (count($progress) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($progress as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['NoiDung'] ?? ''); ?></h6>
                                    <small><?php echo date('d/m/Y H:i', strtotime($item['NgayCapNhat'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($item['GhiChu'] ?? ''); ?></p>
                                <small>
                                    Trạng thái: 
                                    <span class="badge bg-<?php echo $item['TrangThai'] == 'Đã hoàn thành' ? 'success' : ($item['TrangThai'] == 'Đang thực hiện' ? 'primary' : 'warning'); ?>">
                                        <?php echo $item['TrangThai']; ?>
                                    </span>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Chưa có báo cáo tiến độ nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 