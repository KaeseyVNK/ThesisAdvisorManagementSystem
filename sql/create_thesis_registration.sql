-- Tạo bảng DangKyDeTai để lưu trữ đăng ký đề tài của sinh viên
CREATE TABLE IF NOT EXISTS DangKyDeTai (
    DangKyID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienID INT NOT NULL,
    DeTaiID INT NOT NULL,
    GiangVienID INT NOT NULL,
    NgayDangKy DATETIME DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('Chờ duyệt', 'Đã duyệt', 'Từ chối') DEFAULT 'Chờ duyệt',
    LyDoDangKy TEXT,
    LyDoTuChoi TEXT,
    NgayDuyet DATETIME NULL,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE,
    FOREIGN KEY (DeTaiID) REFERENCES DeTai(DeTaiID) ON DELETE CASCADE,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm chỉ mục để tăng tốc độ truy vấn
CREATE INDEX idx_dangky_sinhvien ON DangKyDeTai(SinhVienID);
CREATE INDEX idx_dangky_detai ON DangKyDeTai(DeTaiID);
CREATE INDEX idx_dangky_giangvien ON DangKyDeTai(GiangVienID);
CREATE INDEX idx_dangky_trangthai ON DangKyDeTai(TrangThai);

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