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

// Get theses assigned to this faculty
$stmt = $conn->prepare("
    SELECT DISTINCT dt.*, 
           (SELECT COUNT(*) 
            FROM SinhVienGiangVienHuongDan 
            WHERE DeTaiID = dt.DeTaiID 
            AND GiangVienID = :facultyId 
            AND TrangThai = 'Đang hướng dẫn') as SoSinhVienHienTai
    FROM DeTai dt
    JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
    WHERE svgv.GiangVienID = :facultyId
    ORDER BY dt.NgayTao DESC
");
$stmt->bindParam(':facultyId', $facultyId);
$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$pageTitle = 'Đề tài được phân công';
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Đề tài được phân công</li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fas fa-book me-2"></i>Đề tài được phân công
                </h2>
                <p class="card-text">Danh sách đề tài bạn được phân công hướng dẫn.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách đề tài</h5>
            </div>
            <div class="card-body">
                <?php if (count($theses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tên đề tài</th>
                                    <th>Lĩnh vực</th>
                                    <th>Mô tả</th>
                                    <th>Ngày được phân công</th>
                                    <th>Số SV hiện tại</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($theses as $thesis): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($thesis['TenDeTai']); ?></td>
                                        <td><?php echo htmlspecialchars($thesis['LinhVuc']); ?></td>
                                        <td>
                                            <?php 
                                            $moTa = htmlspecialchars($thesis['MoTa']);
                                            echo strlen($moTa) > 100 ? substr($moTa, 0, 100) . '...' : $moTa;
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $thesis['SoSinhVienHienTai']; ?> sinh viên
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($thesis['TrangThai'] == 'Đã duyệt'): ?>
                                                <span class="badge bg-success"><?php echo $thesis['TrangThai']; ?></span>
                                            <?php elseif ($thesis['TrangThai'] == 'Chờ duyệt'): ?>
                                                <span class="badge bg-warning"><?php echo $thesis['TrangThai']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?php echo $thesis['TrangThai']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info view-details" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#thesisDetailModal"
                                                    data-thesis-id="<?php echo $thesis['DeTaiID']; ?>"
                                                    data-thesis-title="<?php echo htmlspecialchars($thesis['TenDeTai']); ?>"
                                                    data-thesis-description="<?php echo htmlspecialchars($thesis['MoTa']); ?>">
                                                <i class="fas fa-eye"></i> Chi tiết
                                            </button>
                                            <a href="thesis-students.php?id=<?php echo $thesis['DeTaiID']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-users"></i> Sinh viên
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Bạn chưa được phân công đề tài nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Thesis Detail Modal -->
<div class="modal fade" id="thesisDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đề tài</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="modalThesisTitle"></h5>
                <hr>
                <div id="modalThesisDescription"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle thesis detail modal
    const thesisDetailModal = document.getElementById('thesisDetailModal');
    const modalThesisTitle = document.getElementById('modalThesisTitle');
    const modalThesisDescription = document.getElementById('modalThesisDescription');

    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-thesis-title');
            const description = this.getAttribute('data-thesis-description');
            
            modalThesisTitle.textContent = title;
            modalThesisDescription.textContent = description;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 