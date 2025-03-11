<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tenDeTai = sanitizeInput($_POST['tenDeTai']);
    $moTa = sanitizeInput($_POST['moTa']);
    $linhVuc = sanitizeInput($_POST['linhVuc']);
    $trangThai = sanitizeInput($_POST['trangThai']);
    
    // Validate input
    if (empty($tenDeTai)) {
        $errors[] = 'Tên đề tài không được để trống';
    }
    
    if (empty($linhVuc)) {
        $errors[] = 'Lĩnh vực không được để trống';
    }
    
    // If no errors, add thesis to database
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Check if thesis with the same name already exists
            $stmt = $conn->prepare("SELECT * FROM DeTai WHERE TenDeTai = :tenDeTai");
            $stmt->bindParam(':tenDeTai', $tenDeTai);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Đề tài này đã tồn tại';
            } else {
                // Insert into DeTai table
                $stmt = $conn->prepare("
                    INSERT INTO DeTai (TenDeTai, MoTa, LinhVuc, TrangThai)
                    VALUES (:tenDeTai, :moTa, :linhVuc, :trangThai)
                ");
                
                $stmt->bindParam(':tenDeTai', $tenDeTai);
                $stmt->bindParam(':moTa', $moTa);
                $stmt->bindParam(':linhVuc', $linhVuc);
                $stmt->bindParam(':trangThai', $trangThai);
                $stmt->execute();
                
                setFlashMessage('Thêm đề tài thành công', 'success');
                redirect('theses.php');
            }
        } catch (Exception $e) {
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Include header
$pageTitle = 'Thêm đề tài mới';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="theses.php">Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page">Thêm đề tài</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thêm đề tài mới</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="tenDeTai" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tenDeTai" name="tenDeTai" value="<?php echo isset($_POST['tenDeTai']) ? htmlspecialchars($_POST['tenDeTai']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="moTa" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="moTa" name="moTa" rows="4"><?php echo isset($_POST['moTa']) ? htmlspecialchars($_POST['moTa']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="linhVuc" class="form-label">Lĩnh vực <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="linhVuc" name="linhVuc" value="<?php echo isset($_POST['linhVuc']) ? htmlspecialchars($_POST['linhVuc']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="trangThai" class="form-label">Trạng thái</label>
                                <select class="form-select" id="trangThai" name="trangThai">
                                    <option value="Chờ duyệt" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="Đã duyệt" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Đã duyệt') ? 'selected' : ''; ?>>Đã duyệt</option>
                                    <option value="Từ chối" <?php echo (isset($_POST['trangThai']) && $_POST['trangThai'] === 'Từ chối') ? 'selected' : ''; ?>>Từ chối</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lưu
                        </button>
                        <a href="theses.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 