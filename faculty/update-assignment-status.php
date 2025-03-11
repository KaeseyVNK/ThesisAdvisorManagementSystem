<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    setFlashMessage('Bạn không có quyền truy cập trang này', 'danger');
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $assignmentId = $_POST['assignment_id'];
        $status = $_POST['status'];
        $note = $_POST['note'];
        
        $conn = getDBConnection();
        
        // Get current faculty ID
        $stmt = $conn->prepare("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
        $stmt->bindParam(':userId', $_SESSION['user_id']);
        $stmt->execute();
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        $facultyId = $faculty['GiangVienID'];
        
        // Verify this assignment belongs to the faculty
        $stmt = $conn->prepare("
            SELECT * FROM SinhVienGiangVienHuongDan 
            WHERE ID = :id AND GiangVienID = :facultyId
        ");
        $stmt->bindParam(':id', $assignmentId);
        $stmt->bindParam(':facultyId', $facultyId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('Bạn không có quyền cập nhật trạng thái này');
        }
        
        // Update the assignment status
        $stmt = $conn->prepare("
            UPDATE SinhVienGiangVienHuongDan 
            SET TrangThai = :status, 
                GhiChu = :note,
                NgayKetThuc = :ngayKetThuc
            WHERE ID = :id
        ");
        
        $ngayKetThuc = $status == 'Đã hoàn thành' || $status == 'Đã hủy' ? date('Y-m-d') : null;
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':note', $note);
        $stmt->bindParam(':ngayKetThuc', $ngayKetThuc);
        $stmt->bindParam(':id', $assignmentId);
        $stmt->execute();
        
        setFlashMessage('Cập nhật trạng thái thành công', 'success');
    } catch (Exception $e) {
        setFlashMessage('Lỗi: ' . $e->getMessage(), 'danger');
    }
}

// Redirect back to previous page
$referer = $_SERVER['HTTP_REFERER'] ?? 'assignments.php';
redirect($referer); 