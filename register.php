<?php
declare(strict_types=1);

require __DIR__ . '/pages/register_page.php';
return;

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    redirect_by_role((string) current_user()['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        set_flash('danger', 'Vui lòng điền đầy đủ thông tin bắt buộc.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Địa chỉ email chưa hợp lệ.');
    } elseif (strlen($password) < 6) {
        set_flash('danger', 'Mật khẩu cần tối thiểu 6 ký tự.');
    } elseif ($password !== $confirmPassword) {
        set_flash('danger', 'Xác nhận mật khẩu chưa khớp.');
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = fetch_one($stmt);
        $stmt->close();

        if ($exists) {
            set_flash('danger', 'Email đã tồn tại trong hệ thống.');
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'parent';
            $status = 'active';
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $name, $email, $hashed, $role, $phone, $status);
            $stmt->execute();
            $stmt->close();

            set_flash('success', 'Đăng ký thành công. Hãy đăng nhập để bắt đầu quản lý việc học cho con.');
            header('Location: ' . base_url('login.php'));
            exit;
        }
    }
}

render_header('Đăng ký | PAT-Edu');
?>
<section class="homepage-section">
    <div class="auth-card">
        <div class="row g-0 auth-layout">
            <div class="col-lg-5 auth-side">
                <div class="section-label mb-3">Tạo tài khoản phụ huynh</div>
                <h1 class="display-5 mb-3">Bắt đầu với một cổng quản lý học tập rõ ràng và dễ dùng</h1>
                <p class="page-subtitle mb-4">
                    Sau khi đăng ký, phụ huynh có thể thêm hồ sơ học sinh, đăng ký khóa học, theo dõi điểm số,
                    kiểm tra lịch học, thanh toán và nhận phân tích AI về tiến độ học tập.
                </p>
                <div class="auth-feature-list">
                    <div class="auth-feature-item">
                        <div class="icon-badge">1</div>
                        <div>
                            <strong class="d-block mb-1">Thêm con vào hệ thống</strong>
                            <span class="text-secondary">Tạo hồ sơ học sinh ngay trong dashboard phụ huynh.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="icon-badge">2</div>
                        <div>
                            <strong class="d-block mb-1">Đăng ký khóa học online</strong>
                            <span class="text-secondary">Chọn khóa học, theo dõi thanh toán và trạng thái duyệt.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="icon-badge">3</div>
                        <div>
                            <strong class="d-block mb-1">Nhận cảnh báo học tập</strong>
                            <span class="text-secondary">AI tóm tắt điểm mạnh, điểm yếu và môn cần ôn tập.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 auth-form-area">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <div class="section-label mb-2">Đăng ký nhanh</div>
                        <h2 class="h1 mb-2">Tạo tài khoản trong vài phút</h2>
                        <p class="page-subtitle mb-0">Đây là tài khoản phụ huynh. Học sinh sẽ được thêm sau trong dashboard.</p>
                    </div>
                    <a class="btn btn-outline-secondary" href="<?= e(base_url()) ?>">Về trang chủ</a>
                </div>

                <div class="auth-note mb-4">
                    <strong class="d-block mb-1">Vì sao đăng ký theo kiểu này?</strong>
                    <div>Luồng thực tế của PAT-Edu là phụ huynh tạo tài khoản trước, sau đó thêm con và đăng ký khóa học ngay trong khu vực quản lý.</div>
                </div>

                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-4">Tạo tài khoản phụ huynh</button>
                </form>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
                    <span class="text-secondary">Đã có tài khoản?</span>
                    <a href="<?= e(base_url('login.php')) ?>">Đăng nhập ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
