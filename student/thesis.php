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

// Get thesis details
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT dt.*, svgv.ID as AssignmentID, svgv.NgayBatDau, svgv.TrangThai as AssignmentStatus,
           gv.HoTen as GiangVienHoTen, gv.Email as GiangVienEmail, gv.GiangVienID
    FROM SinhVienGiangVienHuongDan svgv
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
    LIMIT 1
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$thesis = $stmt->fetch(PDO::FETCH_ASSOC);

// Get available thesis topics
$stmt = $conn->query("
    SELECT * FROM DeTai 
    WHERE TrangThai = 'Đã duyệt' 
    AND DeTaiID NOT IN (SELECT DeTaiID FROM SinhVienGiangVienHuongDan WHERE DeTaiID IS NOT NULL)
    ORDER BY TenDeTai
");
$availableTopics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if student has any pending thesis proposals
$stmt = $conn->prepare("
    SELECT dx.*, gv.HoTen as TenGiangVien
    FROM DeTaiSinhVienDeXuat dx
    LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
    WHERE dx.SinhVienID = :studentId
    ORDER BY dx.NgayDeXuat DESC
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of faculty members for proposal
$stmt = $conn->query("
    SELECT GiangVienID, HoTen, ChuyenNganh
    FROM GiangVien
    ORDER BY HoTen
");
$facultyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process thesis registration
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register' && isset($_POST['detai_id'])) {
        $detaiId = $_POST['detai_id'];
        
        try {
            // Check if student has an advisor
            $stmt = $conn->prepare("
                SELECT * FROM SinhVienGiangVienHuongDan 
                WHERE SinhVienID = :studentId AND TrangThai = 'Đang hướng dẫn'
            ");
            $stmt->bindParam(':studentId', $studentId);
            $stmt->execute();
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                // Update existing assignment with thesis topic
                $stmt = $conn->prepare("
                    UPDATE SinhVienGiangVienHuongDan 
                    SET DeTaiID = :detaiId 
                    WHERE ID = :assignmentId
                ");
                $stmt->bindParam(':detaiId', $detaiId);
                $stmt->bindParam(':assignmentId', $assignment['ID']);
                $stmt->execute();
                
                $success = 'Đăng ký đề tài thành công!';
                setFlashMessage($success, 'success');
                redirect('../student/thesis.php');
            } else {
                $errors[] = 'Bạn chưa được phân công giảng viên hướng dẫn. Vui lòng liên hệ quản trị viên.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'propose') {
        $thesisTitle = sanitizeInput($_POST['thesis_title']);
        $thesisDescription = sanitizeInput($_POST['thesis_description']);
        $thesisField = sanitizeInput($_POST['thesis_field']);
        
        if (empty($thesisTitle)) {
            $errors[] = 'Vui lòng nhập tên đề tài';
        }
        
        if (empty($thesisDescription)) {
            $errors[] = 'Vui lòng nhập mô tả đề tài';
        }
        
        if (empty($errors)) {
            try {
                // Kiểm tra cấu trúc bảng
                try {
                    // Thử chèn với NULL cho GiangVienID
                    $stmt = $conn->prepare("
                        INSERT INTO DeTaiSinhVienDeXuat (SinhVienID, GiangVienID, TenDeTai, MoTa, LinhVuc, TrangThai)
                        VALUES (:sinhVienId, NULL, :tenDeTai, :moTa, :linhVuc, 'Chờ duyệt')
                    ");
                    $stmt->bindParam(':sinhVienId', $studentId);
                    $stmt->bindParam(':tenDeTai', $thesisTitle);
                    $stmt->bindParam(':moTa', $thesisDescription);
                    $stmt->bindParam(':linhVuc', $thesisField);
                    $stmt->execute();
                } catch (PDOException $innerEx) {
                    // Nếu lỗi do NULL, thử chèn với giá trị mặc định là 1 (admin)
                    if (strpos($innerEx->getMessage(), "cannot be null") !== false) {
                        $stmt = $conn->prepare("
                            INSERT INTO DeTaiSinhVienDeXuat (SinhVienID, GiangVienID, TenDeTai, MoTa, LinhVuc, TrangThai)
                            VALUES (:sinhVienId, 1, :tenDeTai, :moTa, :linhVuc, 'Chờ duyệt')
                        ");
                        $stmt->bindParam(':sinhVienId', $studentId);
                        $stmt->bindParam(':tenDeTai', $thesisTitle);
                        $stmt->bindParam(':moTa', $thesisDescription);
                        $stmt->bindParam(':linhVuc', $thesisField);
                        $stmt->execute();
                    } else {
                        // Nếu lỗi khác, ném lại ngoại lệ
                        throw $innerEx;
                    }
                }
                
                // Create notification for admin
                $stmt = $conn->prepare("
                    INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                    VALUES (1, :title, :content)
                ");
                $title = 'Đề xuất đề tài mới từ sinh viên';
                $content = "Sinh viên {$studentDetails['HoTen']} đã đề xuất đề tài mới: '{$thesisTitle}'. Vui lòng phân công giảng viên hướng dẫn.";
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->execute();
                
                $notificationId = $conn->lastInsertId();
                
                // Kiểm tra xem bảng ThongBaoAdmin đã tồn tại chưa
                try {
                    // Thử thêm thông báo vào bảng ThongBaoAdmin
                    $stmt = $conn->prepare("
                        INSERT INTO ThongBaoAdmin (ThongBaoID, AdminID)
                        VALUES (:notificationId, 1)
                    ");
                    $stmt->bindParam(':notificationId', $notificationId);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Nếu bảng không tồn tại, thêm vào ThongBaoSinhVien với ID 1 (admin)
                    if (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
                        $stmt = $conn->prepare("
                            INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID)
                            VALUES (:notificationId, 1)
                        ");
                        $stmt->bindParam(':notificationId', $notificationId);
                        $stmt->execute();
                    } else {
                        // Nếu lỗi khác, ném lại ngoại lệ
                        throw $e;
                    }
                }
                
                $success = 'Đề xuất đề tài thành công! Đề tài của bạn đang chờ được duyệt và phân công giảng viên hướng dẫn.';
                setFlashMessage($success, 'success');
                redirect('../student/thesis.php');
            } catch (PDOException $e) {
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'cancel_proposal' && isset($_POST['proposal_id'])) {
        $proposalId = $_POST['proposal_id'];
        
        // Check if proposal belongs to this student and is still pending
        $stmt = $conn->prepare("
            SELECT * FROM DeTaiSinhVienDeXuat 
            WHERE DeXuatID = :proposalId AND SinhVienID = :studentId AND TrangThai = 'Chờ duyệt'
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Delete the proposal
            $stmt = $conn->prepare("DELETE FROM DeTaiSinhVienDeXuat WHERE DeXuatID = :proposalId");
            $stmt->bindParam(':proposalId', $proposalId);
            
            if ($stmt->execute()) {
                setFlashMessage('Đã hủy đề xuất đề tài thành công', 'success');
            } else {
                setFlashMessage('Có lỗi xảy ra khi hủy đề xuất đề tài', 'danger');
            }
        } else {
            setFlashMessage('Không thể hủy đề xuất này', 'danger');
        }
        
        redirect('../student/thesis.php');
    }
}

// Include header
$pageTitle = 'Đề tài luận văn';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Đề tài luận văn</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-book me-2"></i>Đề tài luận văn
                </h2>
                <p class="card-text">Quản lý đề tài luận văn của bạn.</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- Thesis Proposals Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Đề xuất đề tài của bạn</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proposeThesisModal">
                    <i class="fas fa-plus me-1"></i> Đề xuất đề tài mới
                </button>
            </div>
            <div class="card-body">
                <?php if (count($proposals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tên đề tài</th>
                                    <th>Lĩnh vực</th>
                                    <th>Giảng viên</th>
                                    <th>Ngày đề xuất</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proposals as $proposal): ?>
                                    <tr>
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#viewProposalModal<?php echo $proposal['DeXuatID']; ?>">
                                                <?php echo htmlspecialchars($proposal['TenDeTai']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($proposal['LinhVuc']); ?></td>
                                        <td><?php echo $proposal['GiangVienID'] ? htmlspecialchars($proposal['TenGiangVien']) : 'Chưa phân công'; ?></td>
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
                                                <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đề xuất này?');">
                                                    <input type="hidden" name="action" value="cancel_proposal">
                                                    <input type="hidden" name="proposal_id" value="<?php echo $proposal['DeXuatID']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Hủy
                                                    </button>
                                                </form>
                                            <?php elseif ($proposal['TrangThai'] == 'Từ chối'): ?>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#rejectReasonModal<?php echo $proposal['DeXuatID']; ?>">
                                                    <i class="fas fa-info-circle"></i> Lý do
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Không có thao tác</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal for viewing proposal details -->
                                    <div class="modal fade" id="viewProposalModal<?php echo $proposal['DeXuatID']; ?>" tabindex="-1" aria-labelledby="viewProposalModalLabel<?php echo $proposal['DeXuatID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewProposalModalLabel<?php echo $proposal['DeXuatID']; ?>">
                                                        Chi tiết đề xuất
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
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
                                                        <h6>Giảng viên:</h6>
                                                        <p><?php echo $proposal['GiangVienID'] ? htmlspecialchars($proposal['TenGiangVien']) : 'Chưa phân công'; ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Ngày đề xuất:</h6>
                                                        <p><?php echo date('d/m/Y H:i', strtotime($proposal['NgayDeXuat'])); ?></p>
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal for rejection reason -->
                                    <?php if ($proposal['TrangThai'] == 'Từ chối'): ?>
                                    <div class="modal fade" id="rejectReasonModal<?php echo $proposal['DeXuatID']; ?>" tabindex="-1" aria-labelledby="rejectReasonModalLabel<?php echo $proposal['DeXuatID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="rejectReasonModalLabel<?php echo $proposal['DeXuatID']; ?>">
                                                        Lý do từ chối
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if (!empty($proposal['LyDoTuChoi'])): ?>
                                                        <p><?php echo nl2br(htmlspecialchars($proposal['LyDoTuChoi'])); ?></p>
                                                    <?php else: ?>
                                                        <p class="text-muted">Không có lý do được cung cấp.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa đề xuất đề tài nào. Nhấn vào nút "Đề xuất đề tài mới" để bắt đầu.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($thesis && isset($thesis['DeTaiID'])): ?>
<!-- Student has a thesis -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin đề tài đã được phân công</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h3><?php echo $thesis['TenDeTai']; ?></h3>
                        <p class="text-muted">
                            <span class="badge bg-primary me-2"><?php echo $thesis['LinhVuc']; ?></span>
                            <span class="badge <?php echo $thesis['TrangThai'] === 'Đã duyệt' ? 'bg-success' : ($thesis['TrangThai'] === 'Chờ duyệt' ? 'bg-warning' : 'bg-danger'); ?>">
                                <?php echo $thesis['TrangThai']; ?>
                            </span>
                        </p>
                        <div class="mb-4">
                            <h5>Mô tả đề tài</h5>
                            <p><?php echo $thesis['MoTa']; ?></p>
                        </div>
                        <div class="mb-4">
                            <h5>Giảng viên hướng dẫn</h5>
                            <p><i class="fas fa-user me-2"></i><?php echo $thesis['GiangVienHoTen']; ?></p>
                            <p><i class="fas fa-envelope me-2"></i><?php echo $thesis['GiangVienEmail']; ?></p>
                        </div>
                        <div>
                            <h5>Thông tin đăng ký</h5>
                            <p><i class="fas fa-calendar me-2"></i>Ngày bắt đầu: <?php echo date('d/m/Y', strtotime($thesis['NgayBatDau'])); ?></p>
                            <p><i class="fas fa-info-circle me-2"></i>Trạng thái: 
                                <span class="badge bg-primary"><?php echo $thesis['AssignmentStatus']; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Tiến độ thực hiện</h5>
                                <a href="../student/progress.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-tasks me-1"></i>Xem tiến độ
                                </a>
                                <a href="../student/add-progress.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-1"></i>Cập nhật tiến độ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Student doesn't have a thesis -->
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Bạn chưa có đề tài luận văn. Vui lòng chọn một đề tài từ danh sách hoặc đề xuất đề tài mới.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs" id="thesisTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="available-tab" data-bs-toggle="tab" data-bs-target="#available" type="button" role="tab" aria-controls="available" aria-selected="true">
                    <i class="fas fa-list me-1"></i>Đề tài có sẵn
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="propose-tab" data-bs-toggle="tab" data-bs-target="#propose" type="button" role="tab" aria-controls="propose" aria-selected="false">
                    <i class="fas fa-lightbulb me-1"></i>Đề xuất đề tài mới
                </button>
            </li>
        </ul>
        <div class="tab-content" id="thesisTabsContent">
            <div class="tab-pane fade show active" id="available" role="tabpanel" aria-labelledby="available-tab">
                <div class="card border-top-0 rounded-top-0">
                    <div class="card-body">
                        <?php if (count($availableTopics) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tên đề tài</th>
                                            <th>Lĩnh vực</th>
                                            <th>Mô tả</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availableTopics as $topic): ?>
                                            <tr>
                                                <td><?php echo $topic['TenDeTai']; ?></td>
                                                <td><?php echo $topic['LinhVuc']; ?></td>
                                                <td><?php echo substr($topic['MoTa'], 0, 100) . (strlen($topic['MoTa']) > 100 ? '...' : ''); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#thesisModal<?php echo $topic['DeTaiID']; ?>">
                                                        <i class="fas fa-info-circle me-1"></i>Chi tiết
                                                    </button>
                                                    
                                                    <!-- Thesis Details Modal -->
                                                    <div class="modal fade" id="thesisModal<?php echo $topic['DeTaiID']; ?>" tabindex="-1" aria-labelledby="thesisModalLabel<?php echo $topic['DeTaiID']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="thesisModalLabel<?php echo $topic['DeTaiID']; ?>"><?php echo $topic['TenDeTai']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p><strong>Lĩnh vực:</strong> <?php echo $topic['LinhVuc']; ?></p>
                                                                    <p><strong>Mô tả:</strong></p>
                                                                    <p><?php echo $topic['MoTa']; ?></p>
                                                                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y', strtotime($topic['NgayTao'])); ?></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                                                        <input type="hidden" name="action" value="register">
                                                                        <input type="hidden" name="detai_id" value="<?php echo $topic['DeTaiID']; ?>">
                                                                        <button type="submit" class="btn btn-primary">Đăng ký đề tài này</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Hiện tại không có đề tài nào khả dụng. Vui lòng đề xuất đề tài mới hoặc liên hệ giảng viên hướng dẫn.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="propose" role="tabpanel" aria-labelledby="propose-tab">
                <div class="card border-top-0 rounded-top-0">
                    <div class="card-body">
                        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="propose">
                            
                            <div class="mb-3">
                                <label for="thesis_title" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="thesis_title" name="thesis_title" required>
                                <div class="invalid-feedback">Vui lòng nhập tên đề tài</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="thesis_field" class="form-label">Lĩnh vực</label>
                                <input type="text" class="form-control" id="thesis_field" name="thesis_field">
                            </div>
                            
                            <div class="mb-3">
                                <label for="thesis_description" class="form-label">Mô tả đề tài <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="thesis_description" name="thesis_description" rows="5" required></textarea>
                                <div class="invalid-feedback">Vui lòng nhập mô tả đề tài</div>
                                <div class="form-text">Mô tả chi tiết về đề tài, mục tiêu, phạm vi và phương pháp thực hiện.</div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Sau khi đề xuất đề tài, quản trị viên sẽ xem xét và phân công giảng viên hướng dẫn phù hợp cho bạn.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Gửi đề xuất
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<!-- Modal for proposing a new thesis -->
<div class="modal fade" id="proposeThesisModal" tabindex="-1" aria-labelledby="proposeThesisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proposeThesisModalLabel">
                    Đề xuất đề tài mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="thesis_title" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="thesis_title" name="thesis_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_field" class="form-label">Lĩnh vực <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="thesis_field" name="thesis_field" required>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_description" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="thesis_description" name="thesis_description" rows="5" required></textarea>
                        <div class="form-text">Mô tả chi tiết về đề tài, mục tiêu, phạm vi nghiên cứu, và kết quả dự kiến.</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sau khi đề xuất đề tài, quản trị viên sẽ xem xét và phân công giảng viên hướng dẫn phù hợp cho bạn.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <input type="hidden" name="action" value="propose">
                    <button type="submit" class="btn btn-primary">Gửi đề xuất</button>
                </div>
            </form>
        </div>
    </div>
</div> 