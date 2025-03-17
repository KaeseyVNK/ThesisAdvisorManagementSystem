<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get student details
$studentDetails = getCurrentUserDetails();

// Kiểm tra nếu không tìm thấy thông tin sinh viên
if (!$studentDetails) {
    setFlashMessage('Không tìm thấy thông tin sinh viên cho tài khoản của bạn. Vui lòng liên hệ quản trị viên.', 'danger');
    redirect('../dashboard.php');
}

$studentId = $studentDetails['SinhVienID'];

// Lấy thông tin trạng thái đề tài của sinh viên
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT sv.*, u.Email as UserEmail
    FROM SinhVien sv
    LEFT JOIN Users u ON sv.UserID = u.UserID
    WHERE sv.SinhVienID = :studentId
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$studentStatus = $stmt->fetch(PDO::FETCH_ASSOC);

// Get assignment details
$stmt = $conn->prepare("
    SELECT svgv.*, dt.TenDeTai, dt.MoTa as MoTaDeTai, dt.LinhVuc, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
    FROM SinhVienGiangVienHuongDan svgv
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
    LIMIT 1
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra xem sinh viên có đề xuất đề tài đã được duyệt không
if (!$assignment) {
    $stmt = $conn->prepare("
        SELECT dx.*, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
        FROM DeTaiSinhVienDeXuat dx
        LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
        WHERE dx.SinhVienID = :studentId AND dx.TrangThai = 'Đã duyệt'
        ORDER BY dx.NgayDuyet DESC
        LIMIT 1
    ");
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();
    $approvedProposal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nếu sinh viên có trạng thái "Đã có đề tài" nhưng không tìm thấy thông tin assignment,
    // thử tìm thông tin đề tài từ bảng DeTai dựa trên đề xuất đã được duyệt
    if (!$approvedProposal && isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài') {
        // Tìm đề tài mới nhất được tạo cho sinh viên này từ bảng SinhVienGiangVienHuongDan
        $stmt = $conn->prepare("
            SELECT dt.*, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
            FROM DeTai dt
            LEFT JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
            LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE svgv.SinhVienID = :studentId
            ORDER BY dt.NgayTao DESC
            LIMIT 1
        ");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $approvedThesis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu không tìm thấy từ SinhVienGiangVienHuongDan, tìm từ DeTaiSinhVienDeXuat đã được duyệt
        if (!$approvedThesis) {
            $stmt = $conn->prepare("
                SELECT dt.*, dx.GiangVienID, gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail
                FROM DeTai dt
                JOIN DeTaiSinhVienDeXuat dx ON dt.TenDeTai = dx.TenDeTai AND dt.MoTa = dx.MoTa
                LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
                WHERE dx.SinhVienID = :studentId AND dx.TrangThai = 'Đã duyệt'
                ORDER BY dt.NgayTao DESC
                LIMIT 1
            ");
            $stmt->bindParam(':studentId', $studentId);
            $stmt->execute();
            $approvedThesis = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Get progress records
$progresses = [];
if ($assignment) {
    $assignmentId = $assignment['ID'];
    $stmt = $conn->prepare("
        SELECT * FROM TienDo
        WHERE SinhVienGiangVienID = :assignmentId
        ORDER BY NgayCapNhat DESC
    ");
    $stmt->bindParam(':assignmentId', $assignmentId);
    $stmt->execute();
    $progresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include header
$pageTitle = 'Tiến độ luận văn';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tiến độ luận văn</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-tasks me-2"></i>Tiến độ luận văn
                </h2>
                <p class="card-text">Theo dõi tiến độ thực hiện luận văn của bạn.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($assignment): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> 
                            <?php 
                            if (!empty($assignment['TenDeTai'])) {
                                echo htmlspecialchars($assignment['TenDeTai']);
                            } elseif (isset($approvedProposal) && !empty($approvedProposal['TenDeTai'])) {
                                echo htmlspecialchars($approvedProposal['TenDeTai']);
                            } elseif (isset($approvedThesis) && !empty($approvedThesis['TenDeTai'])) {
                                echo htmlspecialchars($approvedThesis['TenDeTai']);
                            } elseif (isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài') {
                                echo '<span class="text-success">Đã có đề tài (đang cập nhật thông tin)</span>';
                            } else {
                                echo 'Chưa có đề tài';
                            }
                            ?>
                        </p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo htmlspecialchars($assignment['GiangVienHoTen'] ?? ''); ?></p>
                        <p><strong>Email giảng viên:</strong> <?php echo htmlspecialchars($assignment['GiangVienEmail'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($assignment['LinhVuc'] ?? ''); ?></p>
                        <p><strong>Ngày bắt đầu:</strong> <?php echo !empty($assignment['NgayBatDau']) ? date('d/m/Y', strtotime($assignment['NgayBatDau'])) : 'Chưa xác định'; ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-primary"><?php echo $assignment['TrangThai'] ?? 'Chưa xác định'; ?></span></p>
                    </div>
                </div>
                <?php if (!empty($assignment['MoTaDeTai'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Mô tả đề tài:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($assignment['MoTaDeTai'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <a href="../student/add-progress.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Cập nhật tiến độ mới
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách tiến độ -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Lịch sử cập nhật tiến độ</h5>
            </div>
            <div class="card-body">
                <?php if (count($progresses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nội dung</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày cập nhật</th>
                                    <th>Phản hồi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progresses as $progress): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($progress['NoiDung'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $progress['TrangThai'] == 'Đã hoàn thành' ? 'success' : 
                                                ($progress['TrangThai'] == 'Đang thực hiện' ? 'primary' : 'warning'); 
                                        ?>">
                                            <?php echo $progress['TrangThai']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($progress['NgayCapNhat'])); ?></td>
                                    <td><?php echo $progress['PhanHoi'] ? nl2br(htmlspecialchars($progress['PhanHoi'])) : 'Chưa có phản hồi'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa có bản cập nhật tiến độ nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($approvedProposal) && $approvedProposal): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài đã được duyệt</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Đề xuất đề tài của bạn đã được duyệt. Hệ thống đang cập nhật thông tin, vui lòng chờ trong giây lát.
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> <?php echo htmlspecialchars($approvedProposal['TenDeTai'] ?? ''); ?></p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo htmlspecialchars($approvedProposal['GiangVienHoTen'] ?? ''); ?></p>
                        <p><strong>Ngày duyệt:</strong> <?php echo !empty($approvedProposal['NgayDuyet']) ? date('d/m/Y', strtotime($approvedProposal['NgayDuyet'])) : 'Chưa xác định'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($approvedProposal['LinhVuc'] ?? ''); ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-success">Đã duyệt</span></p>
                    </div>
                </div>
                <?php if (!empty($approvedProposal['MoTa'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Mô tả đề tài:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($approvedProposal['MoTa'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($approvedThesis) && $approvedThesis): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Đề tài của bạn đã được duyệt. Bạn có thể bắt đầu cập nhật tiến độ.
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Đề tài:</strong> <?php echo htmlspecialchars($approvedThesis['TenDeTai'] ?? ''); ?></p>
                        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo htmlspecialchars($approvedThesis['GiangVienHoTen'] ?? ''); ?></p>
                        <p><strong>Email giảng viên:</strong> <?php echo htmlspecialchars($approvedThesis['GiangVienEmail'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lĩnh vực:</strong> <?php echo htmlspecialchars($approvedThesis['LinhVuc'] ?? ''); ?></p>
                        <p><strong>Ngày tạo:</strong> <?php echo !empty($approvedThesis['NgayTao']) ? date('d/m/Y', strtotime($approvedThesis['NgayTao'])) : 'Chưa xác định'; ?></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-success">Đã duyệt</span></p>
                    </div>
                </div>
                <?php if (!empty($approvedThesis['MoTa'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Mô tả đề tài:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($approvedThesis['MoTa'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <a href="../student/add-progress.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Cập nhật tiến độ mới
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($studentStatus) && $studentStatus['TrangThaiDeTai'] == 'Đã có đề tài'): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Đề tài của bạn đã được duyệt và đang được cập nhật trong hệ thống. Vui lòng làm mới trang sau vài phút để xem thông tin chi tiết.
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Đề tài:</strong> <span class="text-success">Đã có đề tài</span></p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-success">Đã duyệt</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>Bạn chưa có đề tài luận văn. Vui lòng <a href="../student/thesis.php">đăng ký đề tài</a> hoặc <a href="../student/thesis.php">đề xuất đề tài mới</a>.
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 