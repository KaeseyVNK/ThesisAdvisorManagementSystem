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

// Xử lý tạo bài tập mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $studentIds = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $allStudents = isset($_POST['all_students']) && $_POST['all_students'] == 1;
    
    // Validate input
    if (empty($title) || empty($description) || empty($deadline)) {
        setFlashMessage('Vui lòng điền đầy đủ thông tin bài tập', 'danger');
    } else {
        try {
            $conn->beginTransaction();
            
            // Upload file if provided
            $attachmentPath = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $uploadDir = '../uploads/assignments/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['attachment']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                    $attachmentPath = 'uploads/assignments/' . $fileName;
                } else {
                    throw new Exception('Không thể tải lên file đính kèm');
                }
            }
            
            // Insert assignment
            $stmt = $conn->prepare("
                INSERT INTO BaiTap (GiangVienID, TieuDe, MoTa, FileDinhKem, HanNop, NgayTao)
                VALUES (:facultyId, :title, :description, :attachment, :deadline, NOW())
            ");
            $stmt->bindParam(':facultyId', $facultyId);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':attachment', $attachmentPath);
            $stmt->bindParam(':deadline', $deadline);
            $stmt->execute();
            
            $assignmentId = $conn->lastInsertId();
            
            // Get students to assign
            if ($allStudents) {
                // Get all students supervised by this faculty
                $stmt = $conn->prepare("
                    SELECT SinhVienID FROM SinhVien 
                    WHERE GiangVienID = :facultyId
                ");
                $stmt->bindParam(':facultyId', $facultyId);
                $stmt->execute();
                $studentIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'SinhVienID');
            }
            
            // Assign to students
            if (!empty($studentIds)) {
                $insertValues = [];
                $insertParams = [];
                
                foreach ($studentIds as $index => $studentId) {
                    $paramName = ":studentId{$index}";
                    $insertValues[] = "(:assignmentId, {$paramName}, 0, NULL)";
                    $insertParams[$paramName] = $studentId;
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO BaiTapSinhVien (BaiTapID, SinhVienID, DaDoc, NgayDoc)
                    VALUES " . implode(', ', $insertValues)
                );
                $stmt->bindParam(':assignmentId', $assignmentId);
                
                foreach ($insertParams as $param => $value) {
                    $stmt->bindValue($param, $value);
                }
                
                $stmt->execute();
            }
            
            $conn->commit();
            setFlashMessage('Bài tập đã được tạo thành công', 'success');
            redirect('assignments.php');
        } catch (Exception $e) {
            $conn->rollBack();
            setFlashMessage('Đã xảy ra lỗi: ' . $e->getMessage(), 'danger');
        }
    }
}

// Xử lý xóa bài tập
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $assignmentId = $_GET['id'];
    
    try {
        // Kiểm tra quyền xóa
        $stmt = $conn->prepare("
            SELECT BaiTapID FROM BaiTap 
            WHERE BaiTapID = :id AND GiangVienID = :facultyId
        ");
        $stmt->bindParam(':id', $assignmentId);
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $conn->beginTransaction();
            
            // Xóa các bản ghi liên quan trong bảng BaiTapSinhVien
            $stmt = $conn->prepare("DELETE FROM BaiTapSinhVien WHERE BaiTapID = :id");
            $stmt->bindParam(':id', $assignmentId);
            $stmt->execute();
            
            // Xóa các bản ghi liên quan trong bảng NopBaiTap
            $stmt = $conn->prepare("DELETE FROM NopBaiTap WHERE BaiTapID = :id");
            $stmt->bindParam(':id', $assignmentId);
            $stmt->execute();
            
            // Xóa bài tập
            $stmt = $conn->prepare("DELETE FROM BaiTap WHERE BaiTapID = :id");
            $stmt->bindParam(':id', $assignmentId);
            $stmt->execute();
            
            $conn->commit();
            setFlashMessage('Bài tập đã được xóa thành công', 'success');
        } else {
            setFlashMessage('Bạn không có quyền xóa bài tập này', 'danger');
        }
    } catch (Exception $e) {
        $conn->rollBack();
        setFlashMessage('Đã xảy ra lỗi: ' . $e->getMessage(), 'danger');
    }
    
    redirect('assignments.php');
}

// Lấy danh sách bài tập
$stmt = $conn->prepare("
    SELECT bt.*, 
           (SELECT COUNT(*) FROM BaiTapSinhVien WHERE BaiTapID = bt.BaiTapID) AS TongSinhVien,
           (SELECT COUNT(*) FROM NopBaiTap WHERE BaiTapID = bt.BaiTapID) AS DaNop
    FROM BaiTap bt
    WHERE bt.GiangVienID = :facultyId
    ORDER BY bt.NgayTao DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sinh viên
$stmt = $conn->prepare("
    SELECT sv.SinhVienID, sv.MaSV, sv.HoTen 
    FROM SinhVien sv
    JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
    WHERE svgv.GiangVienID = :facultyId AND svgv.TrangThai = 'Đang hướng dẫn'
    ORDER BY sv.HoTen
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý bài tập';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý bài tập</li>
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
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tạo bài tập mới</h5>
                </div>
                <div class="card-body">
                    <form action="assignments.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề bài tập <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả bài tập <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attachment" class="form-label">File đính kèm</label>
                            <input type="file" class="form-control" id="attachment" name="attachment">
                            <div class="form-text">Tải lên file hướng dẫn hoặc tài liệu liên quan (nếu có)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Hạn nộp bài <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gửi cho sinh viên</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="all_students" name="all_students" value="1">
                                <label class="form-check-label" for="all_students">
                                    Gửi cho tất cả sinh viên
                                </label>
                            </div>
                            
                            <div id="student_selection" class="card">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <input type="text" class="form-control" id="student_search" placeholder="Tìm kiếm sinh viên...">
                                    </div>
                                    
                                    <?php if (count($students) > 0): ?>
                                    <div class="student-list" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($students as $student): ?>
                                        <div class="form-check student-item">
                                            <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="<?php echo $student['SinhVienID']; ?>" id="student_<?php echo $student['SinhVienID']; ?>">
                                            <label class="form-check-label" for="student_<?php echo $student['SinhVienID']; ?>">
                                                <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen']); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Bạn chưa có sinh viên nào.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Tạo bài tập
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
                    <h5 class="card-title mb-0">Danh sách bài tập</h5>
                </div>
                <div class="card-body">
                    <?php if (count($assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Hạn nộp</th>
                                    <th>Ngày tạo</th>
                                    <th>Số SV</th>
                                    <th>Đã nộp</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['TieuDe']); ?></td>
                                    <td>
                                        <?php 
                                        $deadline = new DateTime($assignment['HanNop']);
                                        $now = new DateTime();
                                        echo date('d/m/Y H:i', strtotime($assignment['HanNop']));
                                        
                                        if ($deadline < $now) {
                                            echo ' <span class="badge bg-danger">Đã hết hạn</span>';
                                        } elseif ($deadline->diff($now)->days < 2) {
                                            echo ' <span class="badge bg-warning text-dark">Sắp hết hạn</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($assignment['NgayTao'])); ?></td>
                                    <td><?php echo $assignment['TongSinhVien']; ?></td>
                                    <td>
                                        <?php echo $assignment['DaNop']; ?> / <?php echo $assignment['TongSinhVien']; ?>
                                        <?php if ($assignment['TongSinhVien'] > 0): ?>
                                        (<?php echo round(($assignment['DaNop'] / $assignment['TongSinhVien']) * 100); ?>%)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view-assignment.php?id=<?php echo $assignment['BaiTapID']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="assignments.php?action=delete&id=<?php echo $assignment['BaiTapID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa bài tập này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa tạo bài tập nào.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý tìm kiếm sinh viên
    const studentSearch = document.getElementById('student_search');
    const studentItems = document.querySelectorAll('.student-item');
    
    if (studentSearch) {
        studentSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            studentItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Xử lý checkbox "Gửi cho tất cả sinh viên"
    const allStudentsCheckbox = document.getElementById('all_students');
    const studentSelection = document.getElementById('student_selection');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    
    if (allStudentsCheckbox && studentSelection) {
        allStudentsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                studentSelection.style.display = 'none';
                studentCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                studentSelection.style.display = 'block';
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>