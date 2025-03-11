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
    setFlashMessage('ID đề tài không hợp lệ', 'danger');
    redirect('theses.php');
}

$thesisId = $_GET['id'];
$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $tenDeTai = trim($_POST['tenDeTai']);
        $moTa = trim($_POST['moTa']);
        $linhVuc = trim($_POST['linhVuc']);
        $trangThai = trim($_POST['trangThai']);

        // Basic validation
        if (empty($tenDeTai) || empty($linhVuc)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }

        // Check if thesis name already exists for different thesis
        $stmt = $conn->prepare("SELECT DeTaiID FROM DeTai WHERE TenDeTai = :tenDeTai AND DeTaiID != :id");
        $stmt->bindParam(':tenDeTai', $tenDeTai);
        $stmt->bindParam(':id', $thesisId);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            throw new Exception('Tên đề tài đã tồn tại');
        }

        // Update thesis
        $stmt = $conn->prepare("
            UPDATE DeTai SET 
            TenDeTai = :tenDeTai,
            MoTa = :moTa,
            LinhVuc = :linhVuc,
            TrangThai = :trangThai
            WHERE DeTaiID = :id
        ");

        $stmt->bindParam(':tenDeTai', $tenDeTai);
        $stmt->bindParam(':moTa', $moTa);
        $stmt->bindParam(':linhVuc', $linhVuc);
        $stmt->bindParam(':trangThai', $trangThai);
        $stmt->bindParam(':id', $thesisId);

        $stmt->execute();

        setFlashMessage('Cập nhật thông tin đề tài thành công', 'success');
        redirect("view-thesis.php?id=$thesisId");

    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Get thesis data
try {
    $stmt = $conn->prepare("SELECT * FROM DeTai WHERE DeTaiID = :id");
    $stmt->bindParam(':id', $thesisId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('Không tìm thấy đề tài', 'danger');
        redirect('theses.php');
    }
    
    $thesis = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    redirect('theses.php');
}

// Include header
$pageTitle = 'Chỉnh sửa đề tài';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="theses.php">Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa đề tài</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Chỉnh sửa thông tin đề tài</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="tenDeTai" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tenDeTai" name="tenDeTai" value="<?php echo htmlspecialchars($thesis['TenDeTai']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="moTa" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="moTa" name="moTa" rows="4"><?php echo htmlspecialchars($thesis['MoTa']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="linhVuc" class="form-label">Lĩnh vực <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="linhVuc" name="linhVuc" value="<?php echo htmlspecialchars($thesis['LinhVuc']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="trangThai" class="form-label">Trạng thái</label>
                        <select class="form-select" id="trangThai" name="trangThai">
                            <option value="Chờ duyệt" <?php echo $thesis['TrangThai'] == 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="Đã duyệt" <?php echo $thesis['TrangThai'] == 'Đã duyệt' ? 'selected' : ''; ?>>Đã duyệt</option>
                            <option value="Từ chối" <?php echo $thesis['TrangThai'] == 'Từ chối' ? 'selected' : ''; ?>>Từ chối</option>
                        </select>
                    </div>

                    <div class="text-end mt-3">
                        <a href="theses.php" class="btn btn-secondary me-2">Hủy</a>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 