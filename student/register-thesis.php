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

// Kiểm tra xem sinh viên đã được phân công đề tài chưa
$stmt = $conn->prepare("
    SELECT svgv.*, dt.TenDeTai, gv.HoTen as TenGiangVien
    FROM SinhVienGiangVienHuongDan svgv
    JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.SinhVienID = :studentId AND svgv.TrangThai = 'Đang hướng dẫn'
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$currentAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra xem sinh viên đã đăng ký đề tài nào chưa và đang chờ duyệt
$stmt = $conn->prepare("
    SELECT dk.*, dt.TenDeTai, gv.HoTen as TenGiangVien
    FROM DangKyDeTai dk
    JOIN GiangVien gv ON dk.GiangVienID = gv.GiangVienID
    JOIN DeTai dt ON dk.DeTaiID = dt.DeTaiID
    WHERE dk.SinhVienID = :studentId AND dk.TrangThai = 'Chờ duyệt'
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$pendingRegistration = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra xem sinh viên đã đề xuất đề tài nào chưa và đang chờ duyệt
$stmt = $conn->prepare("
    SELECT dx.*, gv.HoTen as TenGiangVien
    FROM DeTaiSinhVienDeXuat dx
    LEFT JOIN GiangVien gv ON dx.GiangVienID = gv.GiangVienID
    WHERE dx.SinhVienID = :studentId AND dx.TrangThai = 'Chờ duyệt'
");
$stmt->bindParam(':studentId', $studentId);
$stmt->execute();
$pendingProposal = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý đăng ký đề tài
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        $thesisId = $_POST['thesis_id'];
        $facultyId = $_POST['faculty_id'];
        $reason = $_POST['reason'];
        
        // Kiểm tra xem đề tài có tồn tại không
        $stmt = $conn->prepare("SELECT * FROM DeTai WHERE DeTaiID = :thesisId AND TrangThai = 'Đã duyệt'");
        $stmt->bindParam(':thesisId', $thesisId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            setFlashMessage('Đề tài không tồn tại hoặc chưa được duyệt', 'danger');
        } else {
            // Kiểm tra xem giảng viên có tồn tại không
            $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE GiangVienID = :facultyId");
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                setFlashMessage('Giảng viên không tồn tại', 'danger');
            } else {
                // Kiểm tra xem sinh viên đã đăng ký đề tài này chưa
                $stmt = $conn->prepare("
                    SELECT * FROM DangKyDeTai 
                    WHERE SinhVienID = :studentId AND DeTaiID = :thesisId AND GiangVienID = :facultyId
                    AND TrangThai IN ('Chờ duyệt', 'Đã duyệt')
                ");
                $stmt->bindParam(':studentId', $studentId);
                $stmt->bindParam(':thesisId', $thesisId);
                $stmt->bindParam(':facultyId', $facultyId);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    setFlashMessage('Bạn đã đăng ký đề tài này rồi', 'warning');
                } else {
                    // Thêm đăng ký mới
                    $stmt = $conn->prepare("
                        INSERT INTO DangKyDeTai (SinhVienID, DeTaiID, GiangVienID, LyDoDangKy)
                        VALUES (:studentId, :thesisId, :facultyId, :reason)
                    ");
                    $stmt->bindParam(':studentId', $studentId);
                    $stmt->bindParam(':thesisId', $thesisId);
                    $stmt->bindParam(':facultyId', $facultyId);
                    $stmt->bindParam(':reason', $reason);
                    
                    if ($stmt->execute()) {
                        setFlashMessage('Đăng ký đề tài thành công. Vui lòng chờ giảng viên duyệt.', 'success');
                        redirect('register-thesis.php');
                    } else {
                        setFlashMessage('Có lỗi xảy ra khi đăng ký đề tài', 'danger');
                    }
                }
            }
        }
    } elseif ($_POST['action'] === 'propose') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $field = $_POST['field'];
        
        try {
            // Thử chèn với NULL cho GiangVienID
            try {
                $stmt = $conn->prepare("
                    INSERT INTO DeTaiSinhVienDeXuat (SinhVienID, GiangVienID, TenDeTai, MoTa, LinhVuc, TrangThai)
                    VALUES (:studentId, NULL, :title, :description, :field, 'Chờ duyệt')
                ");
                $stmt->bindParam(':studentId', $studentId);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':field', $field);
                $stmt->execute();
            } catch (PDOException $innerEx) {
                // Nếu lỗi do NULL, thử chèn với giá trị mặc định là 1 (admin)
                if (strpos($innerEx->getMessage(), "cannot be null") !== false) {
                    $stmt = $conn->prepare("
                        INSERT INTO DeTaiSinhVienDeXuat (SinhVienID, GiangVienID, TenDeTai, MoTa, LinhVuc, TrangThai)
                        VALUES (:studentId, 1, :title, :description, :field, 'Chờ duyệt')
                    ");
                    $stmt->bindParam(':studentId', $studentId);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':field', $field);
                    $stmt->execute();
                } else {
                    // Nếu lỗi khác, ném lại ngoại lệ
                    throw $innerEx;
                }
            }
            
            // Tạo thông báo cho admin
            $stmt = $conn->prepare("
                INSERT INTO ThongBao (GiangVienID, TieuDe, NoiDung)
                VALUES (1, :title, :content)
            ");
            $title = 'Đề xuất đề tài mới từ sinh viên';
            $content = "Sinh viên {$studentDetails['HoTen']} đã đề xuất đề tài mới: '{$title}'. Vui lòng xem xét và phân công giảng viên hướng dẫn.";
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->execute();
            
            $notificationId = $conn->lastInsertId();
            
            // Thêm thông báo cho admin
            $stmt = $conn->prepare("
                INSERT INTO ThongBaoSinhVien (ThongBaoID, SinhVienID)
                VALUES (:notificationId, 1)
            ");
            $stmt->bindParam(':notificationId', $notificationId);
            $stmt->execute();
            
            setFlashMessage('Đề xuất đề tài thành công! Đề tài của bạn đang chờ được duyệt và phân công giảng viên hướng dẫn.', 'success');
            redirect('register-thesis.php');
        } catch (PDOException $e) {
            setFlashMessage('Có lỗi xảy ra khi đề xuất đề tài: ' . $e->getMessage(), 'danger');
        }
    } elseif ($_POST['action'] === 'cancel_registration') {
        $registrationId = $_POST['registration_id'];
        
        // Kiểm tra xem đăng ký có tồn tại không và thuộc về sinh viên này không
        $stmt = $conn->prepare("
            SELECT * FROM DangKyDeTai 
            WHERE DangKyID = :registrationId AND SinhVienID = :studentId AND TrangThai = 'Chờ duyệt'
        ");
        $stmt->bindParam(':registrationId', $registrationId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            setFlashMessage('Đăng ký không tồn tại hoặc không thể hủy', 'danger');
        } else {
            // Xóa đăng ký
            $stmt = $conn->prepare("DELETE FROM DangKyDeTai WHERE DangKyID = :registrationId");
            $stmt->bindParam(':registrationId', $registrationId);
            
            if ($stmt->execute()) {
                setFlashMessage('Hủy đăng ký đề tài thành công', 'success');
                redirect('register-thesis.php');
            } else {
                setFlashMessage('Có lỗi xảy ra khi hủy đăng ký đề tài', 'danger');
            }
        }
    } elseif ($_POST['action'] === 'cancel_proposal') {
        $proposalId = $_POST['proposal_id'];
        
        // Kiểm tra xem đề xuất có tồn tại không và thuộc về sinh viên này không
        $stmt = $conn->prepare("
            SELECT * FROM DeTaiSinhVienDeXuat 
            WHERE DeXuatID = :proposalId AND SinhVienID = :studentId AND TrangThai = 'Chờ duyệt'
        ");
        $stmt->bindParam(':proposalId', $proposalId);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            setFlashMessage('Đề xuất không tồn tại hoặc không thể hủy', 'danger');
        } else {
            // Xóa đề xuất
            $stmt = $conn->prepare("DELETE FROM DeTaiSinhVienDeXuat WHERE DeXuatID = :proposalId");
            $stmt->bindParam(':proposalId', $proposalId);
            
            if ($stmt->execute()) {
                setFlashMessage('Hủy đề xuất đề tài thành công', 'success');
                redirect('register-thesis.php');
            } else {
                setFlashMessage('Có lỗi xảy ra khi hủy đề xuất đề tài', 'danger');
            }
        }
    }
}

// Lấy danh sách đề tài đã được duyệt
$stmt = $conn->prepare("
    SELECT dt.*, COUNT(svgv.ID) as SoSinhVien
    FROM DeTai dt
    LEFT JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID AND svgv.TrangThai = 'Đang hướng dẫn'
    WHERE dt.TrangThai = 'Đã duyệt'
    GROUP BY dt.DeTaiID
    ORDER BY dt.NgayTao DESC
");
$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách giảng viên
$stmt = $conn->prepare("
    SELECT gv.*, COUNT(svgv.ID) as SoSinhVien
    FROM GiangVien gv
    LEFT JOIN SinhVienGiangVienHuongDan svgv ON gv.GiangVienID = svgv.GiangVienID AND svgv.TrangThai = 'Đang hướng dẫn'
    GROUP BY gv.GiangVienID
    ORDER BY gv.HoTen
");
$stmt->execute();
$faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Đăng ký đề tài';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Đăng ký đề tài</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if ($currentAssignment): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Bạn đã được phân công đề tài</h5>
                <p>Bạn đã được phân công đề tài <strong><?php echo htmlspecialchars($currentAssignment['TenDeTai'] ?? 'Chưa có tên đề tài'); ?></strong> với giảng viên hướng dẫn <strong><?php echo htmlspecialchars($currentAssignment['TenGiangVien']); ?></strong>.</p>
                <p>Ngày bắt đầu: <strong><?php echo date('d/m/Y', strtotime($currentAssignment['NgayBatDau'])); ?></strong></p>
                <p>Trạng thái: <span class="badge bg-primary"><?php echo $currentAssignment['TrangThai']; ?></span></p>
                <a href="thesis.php" class="btn btn-primary">Xem chi tiết đề tài</a>
            </div>
        </div>
    </div>
    <?php elseif ($pendingRegistration): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <h5><i class="fas fa-clock me-2"></i>Bạn đang có đăng ký đề tài đang chờ duyệt</h5>
                <p>Bạn đã đăng ký đề tài <strong><?php echo htmlspecialchars($pendingRegistration['TenDeTai']); ?></strong> với giảng viên hướng dẫn <strong><?php echo htmlspecialchars($pendingRegistration['TenGiangVien']); ?></strong>.</p>
                <p>Ngày đăng ký: <strong><?php echo date('d/m/Y H:i', strtotime($pendingRegistration['NgayDangKy'])); ?></strong></p>
                <p>Trạng thái: <span class="badge bg-warning text-dark"><?php echo $pendingRegistration['TrangThai']; ?></span></p>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="action" value="cancel_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $pendingRegistration['DangKyID']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đăng ký này?');">
                        <i class="fas fa-times me-2"></i>Hủy đăng ký
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php elseif ($pendingProposal): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <h5><i class="fas fa-clock me-2"></i>Bạn đang có đề xuất đề tài đang chờ duyệt</h5>
                <p>Bạn đã đề xuất đề tài <strong><?php echo htmlspecialchars($pendingProposal['TenDeTai']); ?></strong>
                <?php if ($pendingProposal['GiangVienID']): ?>
                với giảng viên hướng dẫn <strong><?php echo htmlspecialchars($pendingProposal['TenGiangVien']); ?></strong>.
                <?php else: ?>
                và đang chờ quản trị viên phân công giảng viên hướng dẫn.
                <?php endif; ?>
                </p>
                <p>Ngày đề xuất: <strong><?php echo date('d/m/Y H:i', strtotime($pendingProposal['NgayDeXuat'])); ?></strong></p>
                <p>Trạng thái: <span class="badge bg-warning text-dark"><?php echo $pendingProposal['TrangThai']; ?></span></p>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="action" value="cancel_proposal">
                    <input type="hidden" name="proposal_id" value="<?php echo $pendingProposal['DeXuatID']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đề xuất này?');">
                        <i class="fas fa-times me-2"></i>Hủy đề xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <ul class="nav nav-tabs" id="registerTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="true">
                        <i class="fas fa-clipboard-list me-2"></i>Đăng ký đề tài có sẵn
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="propose-tab" data-bs-toggle="tab" data-bs-target="#propose" type="button" role="tab" aria-controls="propose" aria-selected="false">
                        <i class="fas fa-lightbulb me-2"></i>Đề xuất đề tài mới
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="registerTabContent">
                <div class="tab-pane fade show active" id="register" role="tabpanel" aria-labelledby="register-tab">
                    <div class="card border-top-0 rounded-top-0">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Danh sách đề tài có sẵn</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên đề tài</th>
                                            <th>Lĩnh vực</th>
                                            <th>Số SV</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($theses) > 0): ?>
                                            <?php foreach ($theses as $thesis): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold"><?php echo htmlspecialchars($thesis['TenDeTai']); ?></span>
                                                        <small class="text-muted"><?php echo mb_substr(htmlspecialchars($thesis['MoTa']), 0, 100) . (mb_strlen($thesis['MoTa']) > 100 ? '...' : ''); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($thesis['LinhVuc']); ?></td>
                                                <td><?php echo $thesis['SoSinhVien']; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary register-thesis" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#registerModal"
                                                        data-thesis-id="<?php echo $thesis['DeTaiID']; ?>"
                                                        data-thesis-title="<?php echo htmlspecialchars($thesis['TenDeTai']); ?>">
                                                        <i class="fas fa-check me-1"></i>Đăng ký
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Không có đề tài nào</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="propose" role="tabpanel" aria-labelledby="propose-tab">
                    <div class="card border-top-0 rounded-top-0">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Đề xuất đề tài mới</h5>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="propose">
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Mô tả đề tài <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                    <div class="form-text">Mô tả chi tiết về đề tài, mục tiêu, phạm vi nghiên cứu, và kết quả dự kiến.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="field" class="form-label">Lĩnh vực</label>
                                    <input type="text" class="form-control" id="field" name="field">
                                </div>
                                
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Sau khi đề xuất đề tài, quản trị viên sẽ xem xét và phân công giảng viên hướng dẫn phù hợp cho bạn.
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Gửi đề xuất
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
</div>

<!-- Modal đăng ký đề tài -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">Đăng ký đề tài</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="thesis_id" id="thesis_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên đề tài</label>
                        <p class="form-control-plaintext" id="thesis_title"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="faculty_id" class="form-label">Giảng viên hướng dẫn <span class="text-danger">*</span></label>
                        <select class="form-select" id="faculty_id" name="faculty_id" required>
                            <option value="">-- Chọn giảng viên --</option>
                            <?php foreach ($faculties as $faculty): ?>
                            <option value="<?php echo $faculty['GiangVienID']; ?>">
                                <?php echo htmlspecialchars($faculty['HoTen']); ?> 
                                (<?php echo htmlspecialchars($faculty['ChuyenNganh']); ?>) - 
                                Đang hướng dẫn: <?php echo $faculty['SoSinhVien']; ?>/<?php echo $faculty['SoLuongSinhVienToiDa']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Lý do đăng ký</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                        <div class="form-text">Giải thích lý do bạn muốn đăng ký đề tài này và kế hoạch thực hiện.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Đăng ký</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý modal đăng ký đề tài
    const registerButtons = document.querySelectorAll('.register-thesis');
    if (registerButtons.length > 0) {
        registerButtons.forEach(button => {
            button.addEventListener('click', function() {
                const thesisId = this.getAttribute('data-thesis-id');
                const thesisTitle = this.getAttribute('data-thesis-title');
                
                document.getElementById('thesis_id').value = thesisId;
                document.getElementById('thesis_title').textContent = thesisTitle;
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 