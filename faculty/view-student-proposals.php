<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$conn = getDBConnection();

// Get current faculty ID
$stmt = $conn->prepare("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
$stmt->bindParam(':userId', $_SESSION['user_id']);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $faculty['GiangVienID'];

// Process form submission for approving or rejecting proposals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['proposal_id'])) {
        $proposalId = $_POST['proposal_id'];
        $action = $_POST['action'];
        
        // Verify the proposal exists and belongs to this faculty
        $stmt = $conn->prepare("
            SELECT * FROM DeTaiSinhVienDeXuat 
            WHERE DeXuatID = :proposalId AND GiangVienID = :facultyId
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($action === 'approve') {
                // Begin transaction
                $conn->beginTransaction();
                
                try {
                    // Update proposal status
                    $stmt = $conn->prepare("
                        UPDATE DeTaiSinhVienDeXuat 
                        SET TrangThai = 'Đã duyệt', NgayDuyet = NOW() 
                        WHERE DeXuatID = :proposalId
                    ");
                    $stmt->bindParam(':proposalId', $proposalId);
                    $stmt->execute();
                    
                    // Create a new thesis from the proposal
                    $stmt = $conn->prepare("
                        INSERT INTO DeTai (TenDeTai, MoTa, LinhVuc, TrangThai, NgayTao)
                        VALUES (:title, :description, :field, 'Đã duyệt', NOW())
                    ");
                    $stmt->bindParam(':title', $proposal['TenDeTai']);
                    $stmt->bindParam(':description', $proposal['MoTa']);
                    $stmt->bindParam(':field', $proposal['LinhVuc']);
                    $stmt->execute();
                    
                    $newThesisId = $conn->lastInsertId();
                    
                    // Kiểm tra xem sinh viên đã có bản ghi trong bảng SinhVienGiangVienHuongDan chưa
                    $stmt = $conn->prepare("
                        SELECT ID FROM SinhVienGiangVienHuongDan 
                        WHERE SinhVienID = :studentId AND TrangThai = 'Đang hướng dẫn'
                    ");
                    $stmt->bindParam(':studentId', $proposal['SinhVienID']);
                    $stmt->execute();
                    $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingAssignment) {
                        // Cập nhật bản ghi đã tồn tại
                        $stmt = $conn->prepare("
                            UPDATE SinhVienGiangVienHuongDan 
                            SET GiangVienID = :facultyId, DeTaiID = :thesisId, NgayBatDau = NOW()
                            WHERE ID = :assignmentId
                        ");
                        $stmt->bindParam(':facultyId', $facultyId);
                        $stmt->bindParam(':thesisId', $newThesisId);
                        $stmt->bindParam(':assignmentId', $existingAssignment['ID']);
                        $stmt->execute();
                    } else {
                        // Tạo bản ghi mới
                        $stmt = $conn->prepare("
                            INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TrangThai)
                            VALUES (:studentId, :facultyId, :thesisId, NOW(), 'Đang hướng dẫn')
                        ");
                        $stmt->bindParam(':studentId', $proposal['SinhVienID']);
                        $stmt->bindParam(':facultyId', $facultyId);
                        $stmt->bindParam(':thesisId', $newThesisId);
                        $stmt->execute();
                    }
                    
                    // Cập nhật trạng thái sinh viên đã có đề tài
                    $stmt = $conn->prepare("
                        UPDATE SinhVien 
                        SET TrangThaiDeTai = 'Đã có đề tài'
                        WHERE SinhVienID = :studentId
                    ");
                    $stmt->bindParam(':studentId', $proposal['SinhVienID']);
                    $stmt->execute();
                    
                    // Create notification for student
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                        VALUES (:facultyId, :title, :content)
                    ");
                    $title = 'Đề xuất đề tài được chấp nhận';
                    $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã được chấp nhận.";
                    $stmt->bindParam(':facultyId', $facultyId);
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
                    
                    $conn->commit();
                    setFlashMessage('Đã duyệt đề xuất đề tài thành công', 'success');
                } catch (Exception $e) {
                    $conn->rollBack();
                    setFlashMessage('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
                }
            } elseif ($action === 'reject') {
                $rejectReason = $_POST['reject_reason'] ?? '';
                
                // Update proposal status
                $stmt = $conn->prepare("
                    UPDATE DeTaiSinhVienDeXuat 
                    SET TrangThai = 'Từ chối', LyDoTuChoi = :reason, NgayDuyet = NOW() 
                    WHERE DeXuatID = :proposalId
                ");
                $stmt->bindParam(':proposalId', $proposalId);
                $stmt->bindParam(':reason', $rejectReason);
                
                if ($stmt->execute()) {
                    // Create notification for student
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                        VALUES (:facultyId, :title, :content)
                    ");
                    $title = 'Đề xuất đề tài bị từ chối';
                    $content = "Đề xuất đề tài '{$proposal['TenDeTai']}' của bạn đã bị từ chối. Lý do: $rejectReason";
                    $stmt->bindParam(':facultyId', $facultyId);
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
                    
                    setFlashMessage('Đã từ chối đề xuất đề tài', 'success');
                } else {
                    setFlashMessage('Có lỗi xảy ra khi từ chối đề xuất', 'danger');
                }
            }
        } else {
            setFlashMessage('Không tìm thấy đề xuất hoặc bạn không có quyền thực hiện hành động này', 'danger');
        }
        
        redirect('view-student-proposals.php');
    }
}

// Get all proposals assigned to this faculty
$stmt = $conn->prepare("
    SELECT dx.*, sv.HoTen as TenSinhVien, sv.MaSV
    FROM DeTaiSinhVienDeXuat dx
    JOIN SinhVien sv ON dx.SinhVienID = sv.SinhVienID
    WHERE dx.GiangVienID = :facultyId
    ORDER BY 
        CASE 
            WHEN dx.TrangThai = 'Chờ duyệt' THEN 1
            WHEN dx.TrangThai = 'Đã duyệt' THEN 2
            WHEN dx.TrangThai = 'Từ chối' THEN 3
        END,
        dx.NgayDeXuat DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Đề xuất đề tài từ sinh viên';
include '../includes/header.php';
?>

<style>
    /* Ngăn chặn hiệu ứng chớp chớp khi hover vào modal */
    .modal {
        pointer-events: none;
    }
    .modal-dialog {
        pointer-events: all;
    }
    .modal-backdrop {
        pointer-events: all;
    }
    /* Thêm CSS để ngăn chặn hiệu ứng sáng tối */
    .modal-content {
        box-shadow: none !important;
        transition: none !important;
    }
    .modal.fade .modal-dialog {
        transition: transform 0.1s ease-out !important;
    }
    .modal-backdrop {
        transition: opacity 0.1s linear !important;
    }
    .modal-backdrop.show {
        opacity: 0.5 !important;
    }
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Đề xuất đề tài từ sinh viên</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-lightbulb me-2"></i>Đề xuất đề tài từ sinh viên
                </h2>
                <p class="card-text">Xem và duyệt các đề xuất đề tài từ sinh viên được phân công cho bạn.</p>
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
                                            <?php if ($proposal['TrangThai'] == 'Chờ duyệt'): ?>
                                                <span class="badge bg-warning">Chờ duyệt</span>
                                            <?php elseif ($proposal['TrangThai'] == 'Đã duyệt'): ?>
                                                <span class="badge bg-success">Đã duyệt</span>
                                            <?php elseif ($proposal['TrangThai'] == 'Từ chối'): ?>
                                                <span class="badge bg-danger">Từ chối</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($proposal['TrangThai'] == 'Chờ duyệt'): ?>
                                                <a href="approve-proposal.php?id=<?php echo $proposal['DeXuatID']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </a>
                                                <a href="reject-proposal.php?id=<?php echo $proposal['DeXuatID']; ?>" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i> Từ chối
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
                                                    <?php if ($proposal['TrangThai'] == 'Từ chối' && !empty($proposal['LyDoTuChoi'])): ?>
                                                        <div class="mb-3">
                                                            <h6>Lý do từ chối:</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($proposal['LyDoTuChoi'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
                        <i class="fas fa-info-circle me-2"></i>Chưa có đề xuất đề tài nào được phân công cho bạn.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 