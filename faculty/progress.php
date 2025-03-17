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

// Xử lý lọc
$filters = [
    'student' => isset($_GET['student']) ? $_GET['student'] : '',
    'thesis' => isset($_GET['thesis']) ? $_GET['thesis'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
];

// Xây dựng câu truy vấn với các điều kiện lọc
$query = "
    SELECT 
        td.TienDoID, td.NoiDung, td.TrangThai, td.NgayCapNhat, td.PhanHoi,
        sv.SinhVienID, sv.HoTen as TenSinhVien, sv.MaSV,
        dt.DeTaiID, dt.TenDeTai,
        svgv.ID as SinhVienGiangVienID
    FROM TienDo td
    JOIN SinhVienGiangVienHuongDan svgv ON td.SinhVienGiangVienID = svgv.ID
    JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
    LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
";

$params = [':facultyId' => $facultyId];

// Thêm điều kiện lọc
if (!empty($filters['student'])) {
    $query .= " AND sv.SinhVienID = :student";
    $params[':student'] = $filters['student'];
}

if (!empty($filters['thesis'])) {
    $query .= " AND dt.DeTaiID = :thesis";
    $params[':thesis'] = $filters['thesis'];
}

if (!empty($filters['status'])) {
    $query .= " AND td.TrangThai = :status";
    $params[':status'] = $filters['status'];
}

if (!empty($filters['date_from'])) {
    $query .= " AND DATE(td.NgayCapNhat) >= :date_from";
    $params[':date_from'] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $query .= " AND DATE(td.NgayCapNhat) <= :date_to";
    $params[':date_to'] = $filters['date_to'];
}

// Sắp xếp kết quả
$query .= " ORDER BY td.NgayCapNhat DESC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$progressItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sinh viên để hiển thị trong dropdown lọc
$stmt = $conn->prepare("
    SELECT DISTINCT sv.SinhVienID, sv.HoTen, sv.MaSV
    FROM SinhVien sv
    JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
    WHERE svgv.GiangVienID = :facultyId
    ORDER BY sv.HoTen
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách đề tài để hiển thị trong dropdown lọc
$stmt = $conn->prepare("
    SELECT DISTINCT dt.DeTaiID, dt.TenDeTai
    FROM DeTai dt
    JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
    ORDER BY dt.TenDeTai
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Quản lý tiến độ';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý tiến độ</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tasks me-2 text-primary"></i>Danh sách tiến độ
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Bộ lọc -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" action="" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="student" class="form-label">Sinh viên</label>
                                            <select name="student" id="student" class="form-select">
                                                <option value="">Tất cả sinh viên</option>
                                                <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['SinhVienID']; ?>" <?php echo $filters['student'] == $student['SinhVienID'] ? 'selected' : ''; ?>>
                                                    <?php echo $student['MaSV'] . ' - ' . $student['HoTen']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="thesis" class="form-label">Đề tài</label>
                                            <select name="thesis" id="thesis" class="form-select">
                                                <option value="">Tất cả đề tài</option>
                                                <?php foreach ($theses as $thesis): ?>
                                                <option value="<?php echo $thesis['DeTaiID']; ?>" <?php echo $filters['thesis'] == $thesis['DeTaiID'] ? 'selected' : ''; ?>>
                                                    <?php echo $thesis['TenDeTai']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="status" class="form-label">Trạng thái</label>
                                            <select name="status" id="status" class="form-select">
                                                <option value="">Tất cả</option>
                                                <option value="Đang thực hiện" <?php echo $filters['status'] == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                                                <option value="Đã hoàn thành" <?php echo $filters['status'] == 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                                <option value="Cần chỉnh sửa" <?php echo $filters['status'] == 'Cần chỉnh sửa' ? 'selected' : ''; ?>>Cần chỉnh sửa</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="date_from" class="form-label">Từ ngày</label>
                                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filters['date_from']; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="date_to" class="form-label">Đến ngày</label>
                                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filters['date_to']; ?>">
                                        </div>
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-2"></i>Lọc
                                            </button>
                                            <a href="progress.php" class="btn btn-secondary ms-2">
                                                <i class="fas fa-sync-alt me-2"></i>Đặt lại
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Danh sách tiến độ -->
                    <?php if (count($progressItems) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Sinh viên</th>
                                        <th>Đề tài</th>
                                        <th>Nội dung</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progressItems as $item): ?>
                                    <tr>
                                        <td><?php echo $item['TienDoID']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['TenSinhVien']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['MaSV']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['TenDeTai'] ?? 'Chưa có đề tài'); ?></td>
                                        <td><?php echo mb_substr(htmlspecialchars($item['NoiDung']), 0, 50) . (mb_strlen($item['NoiDung']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['TrangThai'] == 'Đã hoàn thành' ? 'success' : ($item['TrangThai'] == 'Đang thực hiện' ? 'primary' : 'warning'); ?>">
                                                <?php echo $item['TrangThai']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['NgayCapNhat'])); ?></td>
                                        <td>
                                            <a href="view-progress.php?id=<?php echo $item['TienDoID']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Không tìm thấy tiến độ nào phù hợp với điều kiện lọc.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 