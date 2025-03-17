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
$conn = getDBConnection();

// Đánh dấu bài tập đã đọc nếu có id
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $assignmentId = $_GET['id'];
    
    // Kiểm tra xem bài tập có tồn tại và thuộc về sinh viên này không
    $stmt = $conn->prepare("
        SELECT btsv.BaiTapSinhVienID, btsv.DaDoc
        FROM BaiTapSinhVien btsv
        WHERE btsv.BaiTapID = :assignmentId AND btsv.SinhVienID = :studentId
    ");
    $stmt->bindParam(':assignmentId', $assignmentId);
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();
    
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment && !$assignment['DaDoc']) {
        // Cập nhật trạng thái đã đọc
        $stmt = $conn->prepare("
            UPDATE BaiTapSinhVien
            SET DaDoc = 1, NgayDoc = NOW()
            WHERE BaiTapID = :assignmentId AND SinhVienID = :studentId
        ");
        $stmt->bindParam(':assignmentId', $assignmentId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
    }
}

// Lấy danh sách bài tập
$stmt = $conn->prepare("
    SELECT 
        bt.BaiTapID, bt.TieuDe, bt.MoTa, bt.FileDinhKem, bt.HanNop, bt.NgayTao,
        gv.HoTen as TenGiangVien,
        btsv.DaDoc, btsv.NgayDoc,
        nb.NopBaiTapID, nb.FileNop, nb.NgayNop, nb.DiemSo, nb.TrangThai
    FROM BaiTapSinhVien btsv
    JOIN BaiTap bt ON btsv.BaiTapID = bt.BaiTapID
    JOIN GiangVien gv ON bt.GiangVienID = gv.GiangVienID
    LEFT JOIN NopBaiTap nb ON btsv.BaiTapID = nb.BaiTapID AND btsv.SinhVienID = nb.SinhVienID
    WHERE btsv.SinhVienID = :studentId
    ORDER BY bt.HanNop ASC
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số bài tập chưa đọc
$stmt = $conn->prepare("
    SELECT COUNT(*) as UnreadCount
    FROM BaiTapSinhVien
    WHERE SinhVienID = :studentId AND DaDoc = 0
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['UnreadCount'];

// Đếm số bài tập chưa nộp và chưa hết hạn
$now = new DateTime();
$pendingCount = 0;
foreach ($assignments as $assignment) {
    $deadline = new DateTime($assignment['HanNop']);
    if (empty($assignment['NopBaiTapID']) && $deadline > $now) {
        $pendingCount++;
    }
}

// Include header
$pageTitle = 'Bài tập';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bài tập</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($_SESSION['flash_messages'])): ?>
        <?php foreach ($_SESSION['flash_messages'] as $message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash_messages']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Bài tập
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> mới</span>
                        <?php endif; ?>
                        <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-warning text-dark ms-2"><?php echo $pendingCount; ?> chưa nộp</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Giảng viên</th>
                                    <th>Hạn nộp</th>
                                    <th>Trạng thái</th>
                                    <th>Điểm</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr class="<?php echo !$assignment['DaDoc'] ? 'table-primary' : ''; ?>">
                                    <td>
                                        <?php if (!$assignment['DaDoc']): ?>
                                        <span class="badge bg-primary me-2">Mới</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($assignment['TieuDe']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['TenGiangVien']); ?></td>
                                    <td>
                                        <?php 
                                        $deadline = new DateTime($assignment['HanNop']);
                                        echo date('d/m/Y H:i', strtotime($assignment['HanNop']));
                                        
                                        if ($deadline < $now) {
                                            echo ' <span class="badge bg-danger">Đã hết hạn</span>';
                                        } elseif ($deadline->diff($now)->days < 2) {
                                            echo ' <span class="badge bg-warning text-dark">Sắp hết hạn</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignment['NopBaiTapID'])): ?>
                                            <?php if ($assignment['TrangThai'] == 'Đã chấm'): ?>
                                                <span class="badge bg-success">Đã chấm</span>
                                            <?php elseif ($assignment['TrangThai'] == 'Cần sửa'): ?>
                                                <span class="badge bg-warning text-dark">Cần sửa</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Đã nộp</span>
                                                <small class="d-block text-muted"><?php echo date('d/m/Y H:i', strtotime($assignment['NgayNop'])); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Chưa nộp</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($assignment['DiemSo'])): ?>
                                            <span class="fw-bold"><?php echo $assignment['DiemSo']; ?>/10</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view-assignment.php?id=<?php echo $assignment['BaiTapID']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>Xem
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa có bài tập nào.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 