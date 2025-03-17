-- Thêm trường TrangThaiDeTai vào bảng SinhVien
ALTER TABLE SinhVien
ADD COLUMN TrangThaiDeTai ENUM('Chưa có đề tài', 'Đã có đề tài', 'Đã hoàn thành') DEFAULT 'Chưa có đề tài';

-- Cập nhật trạng thái đề tài cho sinh viên đã được phân công đề tài
UPDATE SinhVien sv
JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
SET sv.TrangThaiDeTai = 'Đã có đề tài'
WHERE svgv.TrangThai = 'Đang hướng dẫn'; 