-- Tạo bảng DeTaiSinhVienDeXuat để lưu trữ đề tài do sinh viên đề xuất
CREATE TABLE IF NOT EXISTS DeTaiSinhVienDeXuat (
    DeXuatID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienID INT NOT NULL,
    GiangVienID INT NOT NULL,
    TenDeTai VARCHAR(255) NOT NULL,
    MoTa TEXT NOT NULL,
    LinhVuc VARCHAR(100),
    NgayDeXuat DATETIME DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('Chờ duyệt', 'Đã duyệt', 'Từ chối') DEFAULT 'Chờ duyệt',
    LyDoTuChoi TEXT,
    NgayDuyet DATETIME NULL,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm chỉ mục để tăng tốc độ truy vấn
CREATE INDEX idx_dexuat_sinhvien ON DeTaiSinhVienDeXuat(SinhVienID);
CREATE INDEX idx_dexuat_giangvien ON DeTaiSinhVienDeXuat(GiangVienID);
CREATE INDEX idx_dexuat_trangthai ON DeTaiSinhVienDeXuat(TrangThai); 