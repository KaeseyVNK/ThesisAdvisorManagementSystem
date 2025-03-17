<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Đánh dấu thông báo đã đọc khi admin truy cập trang này
try {
    // Thử cập nhật bảng ThongBaoAdmin
    $stmt = $conn->prepare("
        UPDATE ThongBaoAdmin 
        SET DaDoc = 1, NgayDoc = NOW() 
        WHERE AdminID = 1 AND DaDoc = 0
    ");
    $stmt->execute();
} catch (PDOException $e) {
    // Nếu bảng không tồn tại, cập nhật ThongBaoSinhVien với ID 1 (admin)
    if (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        $stmt = $conn->prepare("
            UPDATE ThongBaoSinhVien 
            SET DaDoc = 1, NgayDoc = NOW() 
            WHERE SinhVienID = 1 AND DaDoc = 0
        ");
        $stmt->execute();
    }
}

// Process form submission for assigning faculty to proposals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['proposal_id'])) {
        $proposalId = $_POST['proposal_id'];
        $action = $_POST['action'];
        
        if ($action === 'assign') {
            $facultyId = $_POST['faculty_id'];
            
            // Verify the proposal exists
            $stmt = $conn->prepare("
                SELECT * FROM DeTaiSinhVienDeXuat 
                WHERE DeXuatID = :proposalId AND TrangThai = 'Chờ duyệt'
            ");
            $stmt->bindParam(':proposalId', $proposalId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update proposal with assigned faculty
                $stmt = $conn->prepare("
                    UPDATE DeTaiSinhVienDeXuat 
                    SET GiangVienID = :facultyId
                    WHERE DeXuatID = :proposalId
                ");
                $stmt->bindParam(':proposalId', $proposalId);
                $stmt->bindParam(':facultyId', $facultyId);
                
                if ($stmt->execute()) {
                    // Create notification for faculty
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                        VALUES (:facultyId, :title, :content)
                    ");
                    $title = 'Đề xuất đề tài mới cần duyệt';
                    $content = "Bạn được phân công xem xét đề xuất đề tài '{$proposal['TenDeTai']}'. Vui lòng xem xét và phản hồi.";
                    $stmt->bindParam(':facultyId', $facultyId);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':content', $content);
                    $stmt->execute();
                    
                    $notificationId = $conn->lastInsertId();
                    
                    // Thêm thông báo cho giảng viên
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBaoGiangVien (ThongBaoID, GiangVienID)
                        VALUES (:notificationId, :facultyId)
                    ");
                    $stmt->bindParam(':notificationId', $notificationId);
                    $stmt->bindParam(':facultyId', $facultyId);
                    $stmt->execute();
                    
                    // Create notification for student
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                        VALUES (1, :title, :content)
                    ");
                    $title = 'Đề xuất đề tài đã được phân công giảng viên';
                    $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã được phân công cho giảng viên. Vui lòng chờ phản hồi.";
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':content', $content);
                    $stmt->execute();
                    
                    $notificationId = $conn->lastInsertId();
                    
                    // Thêm thông báo cho sinh viên
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID)
                        VALUES (:notificationId, :studentId)
                    ");
                    $stmt->bindParam(':notificationId', $notificationId);
                    $stmt->bindParam(':studentId', $proposal['SinhVienID']);
                    $stmt->execute();
                    
                    setFlashMessage('Đã phân công giảng viên thành công', 'success');
                } else {
                    setFlashMessage('Có lỗi xảy ra khi phân công giảng viên', 'danger');
                }
            } else {
                setFlashMessage('Không tìm thấy đề xuất hoặc đề xuất không còn ở trạng thái chờ duyệt', 'danger');
            }
        }
        
        redirect('student-proposals.php');
    }
}

// Get all pending proposals
$stmt = $conn->prepare("
    SELECT dx.*, sv.HoTen as TenSinhVien, sv.MaSV, gv.HoTen as TenGiangVien
    FROM DeTaiSinhVienDeXuat dx
    JOIN SinhVien sv ON dx.SinhVienID = sv.SinhVienID
    LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
    ORDER BY 
        CASE 
            WHEN dx.TrangThai = 'Chờ duyệt' THEN 1
            WHEN dx.TrangThai = 'Đã duyệt' THEN 2
            WHEN dx.TrangThai = 'Từ chối' THEN 3
        END,
        dx.NgayDeXuat DESC
");
$stmt->execute();
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of faculty members
$stmt = $conn->prepare("
    SELECT gv.*, 
           (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID AND TrangThai = 'Đang hướng dẫn') as SoSinhVien
    FROM GiangVien gv
    ORDER BY gv.HoTen
");
$stmt->execute();
$faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý đề xuất đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý đề xuất đề tài</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-lightbulb me-2"></i>Quản lý đề xuất đề tài
                </h2>
                <p class="card-text">Xem và phân công giảng viên hướng dẫn cho các đề xuất đề tài từ sinh viên.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách đề xuất</h5>
            </div>
            <div class="card-body">
                <?php if (count($proposals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sinh viên</th>
                                    <th>MSSV</th>
                                    <th>Tên đề tài</th>
                                    <th>Lĩnh vực</th>
                                    <th>Ngày đề xuất</th>
                                    <th>Giảng viên</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proposals as $proposal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($proposal['TenSinhVien']); ?></td>
                                        <td><?php echo htmlspecialchars($proposal['MaSV']); ?></td>
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#proposalModal<?php echo $proposal['DeXuatID']; ?>">
                                                <?php echo htmlspecialchars($proposal['TenDeTai']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($proposal['LinhVuc']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($proposal['NgayDeXuat'])); ?></td>
                                        <td>
                                            <?php if ($proposal['GiangVienID']): ?>
                                                <?php echo htmlspecialchars($proposal['TenGiangVien']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa phân công</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($proposal['TrangThai'] == 'Chờ duyệt'): ?>
                                                <span class="badge bg-warning">Chờ duyệt</span>
                                            <?php elseif ($proposal['TrangThai'] == 'Đã duyệt'): ?>
                                                <span class="badge bg-success">Đã duyệt</span>
                                            <?php elseif ($proposal['TrangThai'] == 'Từ chối'): ?>
                                                <span class="badge bg-danger">Từ chối</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($proposal['TrangThai'] == 'Chờ duyệt' && !$proposal['GiangVienID']): ?>
                                                <a href="assign-faculty.php?id=<?php echo $proposal['DeXuatID']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-user-plus"></i> Phân công GV
                                                </a>
                                            <?php elseif ($proposal['TrangThai'] == 'Chờ duyệt' && $proposal['GiangVienID']): ?>
                                                <a href="assign-faculty.php?id=<?php echo $proposal['DeXuatID']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> Đổi GV
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-check"></i> Đã xử lý
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal for viewing proposal details -->
                                    <div class="modal fade" id="proposalModal<?php echo $proposal['DeXuatID']; ?>" tabindex="-1" aria-labelledby="proposalModalLabel<?php echo $proposal['DeXuatID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="proposalModalLabel<?php echo $proposal['DeXuatID']; ?>">
                                                        Chi tiết đề xuất
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6>Sinh viên:</h6>
                                                        <p><?php echo htmlspecialchars($proposal['TenSinhVien']); ?> (<?php echo htmlspecialchars($proposal['MaSV']); ?>)</p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Tên đề tài:</h6>
                                                        <p><?php echo htmlspecialchars($proposal['TenDeTai']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Lĩnh vực:</h6>
                                                        <p><?php echo htmlspecialchars($proposal['LinhVuc']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Mô tả:</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($proposal['MoTa'])); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Ngày đề xuất:</h6>
                                                        <p><?php echo date('d/m/Y H:i', strtotime($proposal['NgayDeXuat'])); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Giảng viên được phân công:</h6>
                                                        <p>
                                                            <?php if ($proposal['GiangVienID']): ?>
                                                                <?php echo htmlspecialchars($proposal['TenGiangVien']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Chưa phân công</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Trạng thái:</h6>
                                                        <p>
                                                            <?php if ($proposal['TrangThai'] == 'Chờ duyệt'): ?>
                                                                <span class="badge bg-warning">Chờ duyệt</span>
                                                            <?php elseif ($proposal['TrangThai'] == 'Đã duyệt'): ?>
                                                                <span class="badge bg-success">Đã duyệt</span>
                                                            <?php elseif ($proposal['TrangThai'] == 'Từ chối'): ?>
                                                                <span class="badge bg-danger">Từ chối</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($proposal['TrangThai'] == 'Từ chối' && !empty($proposal['LyDoTuChoi'])): ?>
                                                        <div class="mb-3">
                                                            <h6>Lý do từ chối:</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($proposal['LyDoTuChoi'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                    <?php if ($proposal['TrangThai'] == 'Chờ duyệt'): ?>
                                                        <a href="assign-faculty.php?id=<?php echo $proposal['DeXuatID']; ?>" class="btn btn-primary">
                                                            <i class="fas fa-user-plus"></i> Phân công giảng viên
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Chưa có đề xuất đề tài nào từ sinh viên.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 