-- Thesis Advisor Management System Database Schema

-- Drop database if exists
DROP DATABASE IF EXISTS ThesisManagementDB;

-- Create database
CREATE DATABASE ThesisManagementDB;
USE ThesisManagementDB;

-- Create Users table for authentication
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Role ENUM('student', 'faculty', 'admin') NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create SinhVien (Students) table
CREATE TABLE SinhVien (
    SinhVienID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    MaSV VARCHAR(20) NOT NULL UNIQUE,
    HoTen VARCHAR(100) NOT NULL,
    NgaySinh DATE,
    GioiTinh ENUM('Nam', 'Nữ', 'Khác'),
    Email VARCHAR(100) NOT NULL,
    SoDienThoai VARCHAR(15),
    DiaChi TEXT,
    Khoa VARCHAR(100),
    ChuyenNganh VARCHAR(100),
    NienKhoa VARCHAR(20),
    TrangThai ENUM('Đang học', 'Đã tốt nghiệp', 'Đã nghỉ học') DEFAULT 'Đang học',
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
);

-- Create GiangVien (Faculty Members) table
CREATE TABLE GiangVien (
    GiangVienID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    MaGV VARCHAR(20) NOT NULL UNIQUE,
    HoTen VARCHAR(100) NOT NULL,
    HocVi VARCHAR(50),
    ChucVu VARCHAR(100),
    Email VARCHAR(100) NOT NULL,
    SoDienThoai VARCHAR(15),
    Khoa VARCHAR(100),
    ChuyenNganh VARCHAR(100),
    SoLuongSinhVienToiDa INT DEFAULT 10,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
);

-- Create DeTai (Thesis Topics) table
CREATE TABLE DeTai (
    DeTaiID INT AUTO_INCREMENT PRIMARY KEY,
    TenDeTai VARCHAR(255) NOT NULL,
    MoTa TEXT,
    LinhVuc VARCHAR(100),
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('Chờ duyệt', 'Đã duyệt', 'Từ chối') DEFAULT 'Chờ duyệt'
);

-- Create SinhVienGiangVienHuongDan (Advisory Mapping) table
CREATE TABLE SinhVienGiangVienHuongDan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienID INT NOT NULL,
    GiangVienID INT NOT NULL,
    DeTaiID INT,
    NgayBatDau DATE,
    NgayKetThuc DATE,
    TrangThai ENUM('Đang hướng dẫn', 'Đã hoàn thành', 'Đã hủy') DEFAULT 'Đang hướng dẫn',
    GhiChu TEXT,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE,
    FOREIGN KEY (DeTaiID) REFERENCES DeTai(DeTaiID) ON DELETE SET NULL
);

-- Create TienDo (Progress Tracking) table
CREATE TABLE TienDo (
    TienDoID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienGiangVienID INT NOT NULL,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NoiDung TEXT NOT NULL,
    PhanHoi TEXT,
    TrangThai ENUM('Đang thực hiện', 'Đã hoàn thành', 'Cần chỉnh sửa') DEFAULT 'Đang thực hiện',
    FOREIGN KEY (SinhVienGiangVienID) REFERENCES SinhVienGiangVienHuongDan(ID) ON DELETE CASCADE
);

-- Insert sample admin user
INSERT INTO Users (Username, Password, Email, Role) 
VALUES ('admin', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'admin@example.com', 'admin');
-- Password is 'admin123' (hashed)

-- Insert sample faculty users
INSERT INTO Users (Username, Password, Email, Role)
VALUES 
('faculty1', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'faculty1@example.com', 'faculty'),
('faculty2', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'faculty2@example.com', 'faculty');

-- Insert sample student users
INSERT INTO Users (Username, Password, Email, Role)
VALUES 
('student1', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'student1@example.com', 'student'),
('student2', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'student2@example.com', 'student'),
('student3', '$2y$10$8mnR1RlduVy9VvyBVc.YpOzQHoJL9JZDlmIXLRjXk5vxYWyMM53Vy', 'student3@example.com', 'student');

-- Insert sample faculty data
INSERT INTO GiangVien (UserID, MaGV, HoTen, HocVi, ChucVu, Email, SoDienThoai, Khoa, ChuyenNganh)
VALUES 
(2, 'GV001', 'Nguyễn Văn A', 'Tiến sĩ', 'Giảng viên', 'faculty1@example.com', '0901234567', 'Công nghệ thông tin', 'Khoa học máy tính'),
(3, 'GV002', 'Trần Thị B', 'Thạc sĩ', 'Giảng viên', 'faculty2@example.com', '0901234568', 'Công nghệ thông tin', 'Kỹ thuật phần mềm');

-- Insert sample student data
INSERT INTO SinhVien (UserID, MaSV, HoTen, NgaySinh, GioiTinh, Email, SoDienThoai, Khoa, ChuyenNganh, NienKhoa)
VALUES 
(4, 'SV001', 'Lê Văn C', '2000-01-15', 'Nam', 'student1@example.com', '0901234569', 'Công nghệ thông tin', 'Khoa học máy tính', '2020-2024'),
(5, 'SV002', 'Phạm Thị D', '2001-05-20', 'Nữ', 'student2@example.com', '0901234570', 'Công nghệ thông tin', 'Kỹ thuật phần mềm', '2020-2024'),
(6, 'SV003', 'Hoàng Văn E', '2000-11-10', 'Nam', 'student3@example.com', '0901234571', 'Công nghệ thông tin', 'Hệ thống thông tin', '2020-2024');

-- Insert sample thesis topics
INSERT INTO DeTai (TenDeTai, MoTa, LinhVuc, TrangThai)
VALUES 
('Phát triển ứng dụng web sử dụng React và Node.js', 'Nghiên cứu và phát triển ứng dụng web hiện đại sử dụng React và Node.js', 'Phát triển web', 'Đã duyệt'),
('Xây dựng hệ thống nhận diện khuôn mặt sử dụng Deep Learning', 'Nghiên cứu và phát triển hệ thống nhận diện khuôn mặt sử dụng các kỹ thuật Deep Learning', 'Trí tuệ nhân tạo', 'Đã duyệt'),
('Phân tích dữ liệu lớn trong lĩnh vực y tế', 'Nghiên cứu và phát triển các phương pháp phân tích dữ liệu lớn trong lĩnh vực y tế', 'Khoa học dữ liệu', 'Chờ duyệt');

-- Insert sample advisory relationships
INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TrangThai)
VALUES 
(1, 1, 1, '2023-09-01', 'Đang hướng dẫn'),
(2, 2, 2, '2023-09-01', 'Đang hướng dẫn');

-- Insert sample progress records
INSERT INTO TienDo (SinhVienGiangVienID, NoiDung, TrangThai)
VALUES 
(1, 'Đã hoàn thành phân tích yêu cầu và thiết kế hệ thống', 'Đã hoàn thành'),
(1, 'Đang phát triển giao diện người dùng', 'Đang thực hiện'),
(2, 'Đã hoàn thành thu thập dữ liệu và tiền xử lý', 'Đã hoàn thành'); 