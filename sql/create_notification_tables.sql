-- Tạo bảng ThongBao để lưu thông báo từ giảng viên
CREATE TABLE IF NOT EXISTS ThongBao (
    ThongBaoID INT AUTO_INCREMENT PRIMARY KEY,
    GiangVienID INT NOT NULL,
    TieuDe VARCHAR(255) NOT NULL,
    NoiDung TEXT NOT NULL,
    FileDinhKem VARCHAR(255) NULL,
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng ThongBaoSinhVien để lưu thông tin về việc gửi thông báo cho sinh viên
CREATE TABLE IF NOT EXISTS ThongBaoSinhVien (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ThongBaoID INT NOT NULL,
    SinhVienID INT NOT NULL,
    DaDoc BOOLEAN DEFAULT FALSE,
    NgayDoc DATETIME NULL,
    FOREIGN KEY (ThongBaoID) REFERENCES ThongBao(ThongBaoID) ON DELETE CASCADE,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng BaiTap để lưu bài tập từ giảng viên
CREATE TABLE IF NOT EXISTS BaiTap (
    BaiTapID INT AUTO_INCREMENT PRIMARY KEY,
    GiangVienID INT NOT NULL,
    TieuDe VARCHAR(255) NOT NULL,
    MoTa TEXT NOT NULL,
    FileDinhKem VARCHAR(255) NULL,
    HanNop DATETIME NOT NULL,
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng BaiTapSinhVien để lưu thông tin về việc giao bài tập cho sinh viên
CREATE TABLE IF NOT EXISTS BaiTapSinhVien (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    BaiTapID INT NOT NULL,
    SinhVienID INT NOT NULL,
    DaDoc BOOLEAN DEFAULT FALSE,
    NgayDoc DATETIME NULL,
    FOREIGN KEY (BaiTapID) REFERENCES BaiTap(BaiTapID) ON DELETE CASCADE,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng NopBaiTap để lưu thông tin bài tập sinh viên đã nộp
CREATE TABLE IF NOT EXISTS NopBaiTap (
    NopBaiTapID INT AUTO_INCREMENT PRIMARY KEY,
    BaiTapID INT NOT NULL,
    SinhVienID INT NOT NULL,
    FileNop VARCHAR(255) NOT NULL,
    GhiChu TEXT NULL,
    NgayNop DATETIME DEFAULT CURRENT_TIMESTAMP,
    DiemSo FLOAT NULL,
    NhanXet TEXT NULL,
    TrangThai ENUM('Đã nộp', 'Đã chấm', 'Trễ hạn') DEFAULT 'Đã nộp',
    FOREIGN KEY (BaiTapID) REFERENCES BaiTap(BaiTapID) ON DELETE CASCADE,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 