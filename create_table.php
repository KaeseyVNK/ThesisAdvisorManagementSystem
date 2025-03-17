<?php
require_once 'config/config.php';

try {
    $conn = getDBConnection();
    
    // Đọc nội dung file SQL
    $sql = file_get_contents('sql/create_detaisinhviendexuat.sql');
    
    // Thực thi SQL
    $conn->exec($sql);
    
    echo "Bảng DeTaiSinhVienDeXuat đã được tạo thành công!";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?> 