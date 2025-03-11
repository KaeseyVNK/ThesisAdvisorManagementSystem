<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('ID giảng viên không hợp lệ', 'danger');
    redirect('lecturers.php');
}

$lecturerId = $_GET['id'];

try {
    $conn = getDBConnection();
    
    // Get lecturer details with user information
    $stmt = $conn->prepare("
        SELECT gv.*, u.Username, u.Email as UserEmail,
        (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID) as SoSinhVienHuongDan
        FROM GiangVien gv
        LEFT JOIN Users u ON gv.UserID = u.UserID
        WHERE gv.GiangVienID = :id
    ");
    $stmt->bindParam(':id', $lecturerId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy giảng viên', 'danger');
        redirect('lecturers.php');
    }
    
    $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get students being advised
    $stmt = $conn->prepare("
        SELECT sv.MaSV, sv.HoTen, sv.Email, sv.ChuyenNganh,
        svgv.NgayBatDau, svgv.TrangThai,
        dt.TenDeTai
        FROM SinhVienGiangVienHuongDan svgv
        JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
        LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
        WHERE svgv.GiangVienID = :id
        ORDER BY svgv.NgayBatDau DESC
    ");
    $stmt->bindParam(':id', $lecturerId);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('lecturers.php');
}

// Include header
$pageTitle = 'Chi tiết giảng viên';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="lecturers.php">Quản lý giảng viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chi tiết giảng viên</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Thông tin giảng viên</h5>
                <div>
                    <a href="
</div>
</div>
</div>
</div>

<?php include '../includes/footer.php'; ?> 