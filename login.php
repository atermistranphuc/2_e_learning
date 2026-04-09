<?php
declare(strict_types=1);

require __DIR__ . '/pages/login_page.php';
return;

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    redirect_by_role((string) current_user()['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = fetch_one($stmt);
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        redirect_by_role((string) $user['role']);
    }

    set_flash('danger', 'Email hoặc mật khẩu không đúng.');
    header('Location: ' . base_url('login.php'));
    exit;
}

render_header('Đăng nhập | PAT-Edu');
?>
<section class="homepage-section">
    <div class="auth-card">
        <div class="row g-0 auth-layout">
            <div class="col-lg-5 auth-side">
                <div class="section-label mb-3">Đăng nhập hệ thống</div>
                <h1 class="display-5 mb-3">Tiếp tục hành trình học tập với PAT-Edu</h1>
                <p class="page-subtitle mb-4">
                    Trang đăng nhập được làm lại để đi sau homepage, không chặn người dùng từ bước đầu.
                    Sau khi đăng nhập, hệ thống sẽ tự chuyển đúng dashboard theo vai trò admin, phụ huynh hoặc học sinh.
                </p>
                <div class="auth-feature-list">
                    <div class="auth-feature-item">
                        <div class="icon-badge">A</div>
                        <div>
                            <strong class="d-block mb-1">Admin quản trị toàn bộ</strong>
                            <span class="text-secondary">Quản lý khóa học, bài học, người dùng, thanh toán và báo cáo.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="icon-badge">P</div>
                        <div>
                            <strong class="d-block mb-1">Phụ huynh đăng ký cho con</strong>
                            <span class="text-secondary">Theo dõi điểm, lịch học, thanh toán và nhận cảnh báo AI.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="icon-badge">S</div>
                        <div>
                            <strong class="d-block mb-1">Học sinh học tập trực tuyến</strong>
                            <span class="text-secondary">Làm bài, nhận feedback, xem lịch học và gợi ý học tập cá nhân hóa.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 auth-form-area">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <div class="section-label mb-2">Welcome back</div>
                        <h2 class="h1 mb-2">Đăng nhập vào tài khoản của bạn</h2>
                        <p class="page-subtitle mb-0">Form này dùng session thực và redirect theo role sau khi xác thực thành công.</p>
                    </div>
                    <a class="btn btn-outline-secondary" href="<?= e(base_url()) ?>">Về trang chủ</a>
                </div>

                <div class="auth-note mb-4">
                    <strong class="d-block mb-1">Tài khoản mẫu</strong>
                    <div>Admin: <code>admin@pat.edu</code> / <code>password</code></div>
                    <div>Parent: <code>parent1@pat.edu</code> / <code>password</code></div>
                    <div>Student: <code>student1@pat.edu</code> / <code>password</code></div>
                </div>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="ban@pat.edu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                    </div>
                    <button class="btn btn-primary w-100 mt-2">Đăng nhập</button>
                </form>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
                    <span class="text-secondary">Chưa có tài khoản phụ huynh?</span>
                    <a href="<?= e(base_url('register.php')) ?>">Tạo tài khoản mới</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
