<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Get faculty details
$facultyDetails = getCurrentUserDetails();

// Kiểm tra nếu không tìm thấy thông tin giảng viên
if (!$facultyDetails) {
    setFlashMessage('Không tìm thấy thông tin giảng viên cho tài khoản của bạn. Vui lòng liên hệ quản trị viên.', 'danger');
    redirect('../dashboard.php');
}

$facultyId = $facultyDetails['GiangVienID'];
$conn = getDBConnection();

// Xử lý tạo thông báo mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notification'])) {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);
    $studentIds = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    
    // Xử lý file đính kèm nếu có
    $attachmentFile = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = '../uploads/notifications/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Upload file
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            $attachmentFile = 'uploads/notifications/' . $fileName;
        } else {
            setFlashMessage('Có lỗi khi tải lên file đính kèm', 'danger');
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // Thêm thông báo mới
        $stmt = $conn->prepare("INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung, FileDinhKem) VALUES (:facultyId, :title, :content, :attachment)");
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':attachment', $attachmentFile);
        $stmt->execute();
        
        $notificationId = $conn->lastInsertId();
        
        // Nếu không chọn sinh viên cụ thể, gửi cho tất cả sinh viên được hướng dẫn
        if (empty($studentIds)) {
            $stmt = $conn->prepare("
                INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID)
                SELECT :notificationId, SinhVienID
                FROM SinhVienGiangVienHuongDan
                WHERE GiangVienID = :facultyId AND TrangThai = 'Đang hướng dẫn'
            ");
            $stmt->bindParam(':notificationId', $notificationId);
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->execute();
        } else {
            // Gửi cho các sinh viên được chọn
            $stmt = $conn->prepare("INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID) VALUES (:notificationId, :studentId)");
            
            foreach ($studentIds as $studentId) {
                $stmt->bindParam(':notificationId', $notificationId);
                $stmt->bindParam(':studentId', $studentId);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        setFlashMessage('Thông báo đã được tạo và gửi thành công', 'success');
        redirect('notifications.php');
        
    } catch (PDOException $e) {
        $conn->rollBack();
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Xử lý xóa thông báo
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notificationId = $_GET['delete'];
    
    try {
        // Kiểm tra xem thông báo có thuộc về giảng viên này không
        $stmt = $conn->prepare("SELECT * FROM ThongBao WHERE ThongBaoID = :id AND GiangVienID = :facultyId");
        $stmt->bindParam(':id', $notificationId);
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Xóa thông báo
            $stmt = $conn->prepare("DELETE FROM ThongBao WHERE ThongBaoID = :id");
            $stmt->bindParam(':id', $notificationId);
            $stmt->execute();
            
            setFlashMessage('Thông báo đã được xóa thành công', 'success');
        } else {
            setFlashMessage('Bạn không có quyền xóa thông báo này', 'danger');
        }
    } catch (PDOException $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('notifications.php');
}

// Lấy danh sách thông báo đã tạo
$stmt = $conn->prepare("
    SELECT tb.*, 
           COUNT(tbsv.ID) as TongSinhVien,
           SUM(CASE WHEN tbsv.DaDoc = 1 THEN 1 ELSE 0 END) as SoDaDoc
    FROM ThongBao tb
    LEFT JOIN ThongBaoSinhVien tbsv ON tb.ThongBaoID = tbsv.ThongBaoID
    WHERE tb.GiangVienID = :facultyId
    GROUP BY tb.ThongBaoID
    ORDER BY tb.NgayTao DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sinh viên được hướng dẫn
$stmt = $conn->prepare("
    SELECT sv.SinhVienID, sv.MaSV, sv.HoTen
    FROM SinhVienGiangVienHuongDan svgv
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    WHERE svgv.GiangVienID = :facultyId AND svgv.TrangThai = 'Đang hướng dẫn'
    ORDER BY sv.HoTen
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý thông báo';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý thông báo</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tạo thông báo mới</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newNotificationForm">
                        <i class="fas fa-plus me-2"></i>Tạo thông báo
                    </button>
                </div>
                <div class="card-body collapse" id="newNotificationForm">
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Nội dung <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attachment" class="form-label">File đính kèm</label>
                            <input type="file" class="form-control" id="attachment" name="attachment">
                            <div class="form-text">Chấp nhận các file: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR (tối đa 10MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gửi đến</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="sendToAll" checked>
                                <label class="form-check-label" for="sendToAll">
                                    Gửi cho tất cả sinh viên đang hướng dẫn
                                </label>
                            </div>
                            
                            <div id="studentSelection" class="d-none">
                                <label class="form-label">Chọn sinh viên</label>
                                <div class="card card-body bg-light">
                                    <?php if (count($students) > 0): ?>
                                        <?php foreach ($students as $student): ?>
                                        <div class="form-check">
                                            <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="<?php echo $student['SinhVienID']; ?>" id="student_<?php echo $student['SinhVienID']; ?>">
                                            <label class="form-check-label" for="student_<?php echo $student['SinhVienID']; ?>">
                                                <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen']); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="mb-0">Không có sinh viên nào đang được hướng dẫn.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="create_notification" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Gửi thông báo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Danh sách thông báo đã gửi</h5>
                </div>
                <div class="card-body">
                    <?php if (count($notifications) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Ngày tạo</th>
                                    <th>Đã đọc</th>
                                    <th>File đính kèm</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($notification['TieuDe']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php 
                                            $readPercentage = $notification['TongSinhVien'] > 0 ? 
                                                round(($notification['SoDaDoc'] / $notification['TongSinhVien']) * 100) : 0;
                                            ?>
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $readPercentage; ?>%;" 
                                                 aria-valuenow="<?php echo $readPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $notification['SoDaDoc'] . '/' . $notification['TongSinhVien']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($notification['FileDinhKem'])): ?>
                                            <a href="../<?php echo $notification['FileDinhKem']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i>Tải xuống
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view-notification.php?id=<?php echo $notification['ThongBaoID']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye me-1"></i>Xem
                                        </a>
                                        <a href="notifications.php?delete=<?php echo $notification['ThongBaoID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                                            <i class="fas fa-trash me-1"></i>Xóa
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa tạo thông báo nào.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý chọn gửi cho tất cả hoặc chọn sinh viên cụ thể
    const sendToAllCheckbox = document.getElementById('sendToAll');
    const studentSelection = document.getElementById('studentSelection');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    
    sendToAllCheckbox.addEventListener('change', function() {
        if (this.checked) {
            studentSelection.classList.add('d-none');
            // Bỏ chọn tất cả các checkbox sinh viên
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        } else {
            studentSelection.classList.remove('d-none');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 