# Thesis Advisor Management System

Hệ thống Quản lý Hướng dẫn Luận văn là một ứng dụng web được thiết kế để giúp quản lý và theo dõi các hoạt động hướng dẫn luận văn giữa sinh viên và giảng viên. Hệ thống cung cấp một nền tảng có cấu trúc để ghi lại thông tin chi tiết của sinh viên, giảng viên hướng dẫn và tương tác của họ trong suốt quá trình hướng dẫn luận văn.

## Tính năng chính

- **Quản lý người dùng**: Đăng ký, đăng nhập và phân quyền người dùng (Sinh viên, Giảng viên, Quản trị viên)
- **Quản lý sinh viên**: Thêm, sửa, xóa và xem thông tin sinh viên
- **Quản lý giảng viên**: Thêm, sửa, xóa và xem thông tin giảng viên
- **Quản lý đề tài**: Thêm, sửa, xóa và xem thông tin đề tài luận văn
- **Phân công hướng dẫn**: Gán sinh viên cho giảng viên hướng dẫn
- **Theo dõi tiến độ**: Cập nhật và theo dõi tiến độ thực hiện luận văn
- **Báo cáo và thống kê**: Tạo báo cáo về quá trình hướng dẫn luận văn

## Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache, Nginx)
- Trình duyệt web hiện đại

## Cài đặt

1. Clone repository về máy local:
   ```
   git clone https://github.com/yourusername/thesis-advisor-management.git
   ```

2. Import cơ sở dữ liệu:
   - Tạo cơ sở dữ liệu mới trong MySQL
   - Import file `database/thesis_management_db.sql`

3. Cấu hình kết nối cơ sở dữ liệu:
   - Mở file `config/config.php`
   - Cập nhật thông tin kết nối cơ sở dữ liệu (host, username, password, database name)

4. Cấu hình web server:
   - Đặt thư mục gốc của web server vào thư mục dự án
   - Đảm bảo mod_rewrite được bật (nếu sử dụng Apache)

5. Truy cập ứng dụng:
   - Mở trình duyệt và truy cập vào địa chỉ của ứng dụng (ví dụ: http://localhost/thesis-advisor-management)

## Tài khoản mặc định

- **Quản trị viên**:
  - Tên đăng nhập: admin
  - Mật khẩu: admin123

- **Giảng viên**:
  - Tên đăng nhập: faculty1
  - Mật khẩu: admin123

- **Sinh viên**:
  - Tên đăng nhập: student1
  - Mật khẩu: admin123

## Cấu trúc thư mục

```
thesis-advisor-management/
├── admin/                 # Trang quản trị
├── assets/                # CSS, JavaScript, hình ảnh
│   ├── css/
│   ├── js/
│   └── images/
├── config/                # Cấu hình ứng dụng
├── database/              # Script cơ sở dữ liệu
├── faculty/               # Trang giảng viên
├── includes/              # Các file include
├── student/               # Trang sinh viên
├── uploads/               # Thư mục upload file
├── index.php              # Trang chủ
├── login.php              # Trang đăng nhập
├── logout.php             # Script đăng xuất
├── dashboard.php          # Bảng điều khiển
└── README.md              # Tài liệu hướng dẫn
```

## Công nghệ sử dụng

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **Libraries**: Font Awesome, jQuery, Chart.js

## Tác giả

- **Tên tác giả** - [GitHub Profile](https://github.com/yourusername)

## Giấy phép

Dự án này được cấp phép theo giấy phép MIT - xem file [LICENSE](LICENSE) để biết thêm chi tiết. 