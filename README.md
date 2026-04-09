# PAT-Edu

PAT-Edu là website trung tâm học online xây dựng bằng PHP + MySQL, chạy trên XAMPP và chỉnh sửa bằng Visual Studio Code.
Mục tiêu của project này là vừa dễ hiểu cho người mới, vừa đủ rõ cấu trúc để phát triển thành một sản phẩm thực tế.

## 1. Công nghệ sử dụng

- Backend: PHP thuần
- Database: MySQL (phpMyAdmin / XAMPP)
- Frontend: HTML + CSS + JavaScript + Bootstrap
- AJAX: `fetch()` đến các endpoint trong `ajax/`
- API demo: `api/courses.php`

## 2. Cấu trúc thư mục

- `config/`: kết nối database, session, auth, helper, header/footer dùng chung
- `database/`: file SQL import nhanh
- `assets/css/`: toàn bộ style giao diện
- `assets/js/`: JavaScript dùng chung và AJAX public
- `admin/`: dashboard và trang quản trị
- `parent/`: dashboard phụ huynh
- `student/`: dashboard học sinh
- `ajax/`: endpoint xử lý không reload trang
- `api/`: JSON API cơ bản để mở rộng sau này
- `docs/`: tài liệu giải thích kiến trúc

## 3. Quy trình xây dựng theo từng bước

### Bước 1: Database

File chính: `database/pat_edu.sql`

Database hiện có các bảng:
- `users`
- `students`
- `categories`
- `courses`
- `enrollments`
- `lessons`
- `scores`
- `schedules`
- `payments`
- `notifications`
- `course_reviews`
- `wishlist_items`
- `contact_messages`
- `payment_logs`

Vì sao làm bước này trước:
- Homepage, danh sách khóa học, review, wishlist, dashboard và AI đều cần dữ liệu chuẩn.
- Nếu bảng và quan hệ chưa đúng thì giao diện đẹp đến đâu cũng không chạy thực tế.

### Bước 2: Cấu trúc project

File tham khảo: `docs/ARCHITECTURE.md`

Vì sao cần tách thư mục rõ ràng:
- Người mới dễ biết file nào phụ trách phần nào.
- Dễ bảo trì và mở rộng sau này.
- Có thể nâng cấp lên MVC rõ hơn hoặc tách frontend/API riêng.

### Bước 3: Homepage

File chính:
- `index.php`
- `config/functions.php`
- `assets/css/style.css`

Homepage hiện có:
- Trang chủ công khai trước khi đăng nhập
- Hero/banner giới thiệu sản phẩm
- Search khóa học thật
- Lọc theo danh mục thật từ database
- Khóa học nổi bật lấy từ MySQL
- Review thật từ bảng `course_reviews`
- Form tư vấn thật lưu vào bảng `contact_messages`
- Footer đầy đủ và CTA rõ ràng

Vì sao homepage là bước ưu tiên:
- Người dùng phải hiểu hệ thống trước khi đăng nhập.
- Đây là phần quyết định ấn tượng đầu tiên và khả năng chuyển đổi.

### Bước 4: Auth

File chính:
- `login.php`
- `register.php`
- `logout.php`
- `config/auth.php`

Đã có:
- Đăng ký tài khoản phụ huynh
- Đăng nhập / đăng xuất
- Session
- Redirect theo role
- `password_hash` / `password_verify`

### Bước 5: Các chức năng còn lại

Đã có sẵn:
- `courses.php`: danh sách khóa học có search, filter, sort, pagination
- `course.php`: chi tiết khóa học, syllabus, review, wishlist
- `ajax/toggle_wishlist.php`: thêm / bỏ yêu thích bằng AJAX
- `ajax/add_review.php`: gửi đánh giá không reload trang
- `admin/`: dashboard quản trị
- `parent/`: dashboard phụ huynh
- `student/`: dashboard học sinh
- `api/courses.php`: API JSON cơ bản

## 4. Cách chạy trên XAMPP

1. Đặt thư mục project vào:
   `C:\xampp\htdocs\elearning\pat_edu`
2. Mở XAMPP Control Panel
3. Bật `Apache`
4. Bật `MySQL`
5. Mở phpMyAdmin và import file:
   `database/pat_edu.sql`
6. Truy cập:
   `http://localhost/elearning/pat_edu/`

Nếu database chưa sẵn sàng:
- mở `http://localhost/elearning/pat_edu/setup.php`

## 5. Tài khoản mẫu

- Admin: `admin@pat.edu` / `password`
- Parent: `parent1@pat.edu` / `password`
- Student: `student1@pat.edu` / `password`

## 6. Muốn chỉnh sửa giao diện thì sửa ở đâu

- Đổi màu, bo góc, card, spacing: `assets/css/style.css`
- Đổi header / footer dùng chung: `config/functions.php`
- Đổi homepage: `index.php`
- Đổi danh sách khóa học: `courses.php`
- Đổi chi tiết khóa học: `course.php`
- Đổi trang đăng nhập / đăng ký: `login.php`, `register.php`

## 7. Bảo mật đang có

- Hash mật khẩu bằng `password_hash`
- Xác thực bằng session
- Prepared statements để giảm nguy cơ SQL Injection
- Validate input cơ bản ở form login/register/contact/review

## 8. Tính năng nâng cao đang có

- AJAX review và wishlist
- Gợi ý học tập bằng AI rule-based
- Chấm trắc nghiệm ngay
- Nhận xét tự luận theo từ khóa
- Dashboard theo vai trò
- API JSON đơn giản
- Form liên hệ / tư vấn hoạt động thật

## 9. Hướng nâng cấp tiếp theo

- Tách model / service rõ hơn để tiến gần MVC hoàn chỉnh
- Thêm quản lý contact/review trong admin
- Tích hợp email thật bằng SMTP
- Tích hợp cổng thanh toán thật
- Tách `api/` thành REST API chuẩn
- Nâng frontend lên React / Next.js nếu cần SPA
