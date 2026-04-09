<?php
declare(strict_types=1);

require __DIR__ . '/pages/index_page.php';
return;

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/auth.php';

$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact_message') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        set_flash('danger', 'Vui lòng điền đầy đủ thông tin tư vấn.');
    } else {
        $stmt = $conn->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, "new")');
        $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'PAT-Edu đã nhận yêu cầu tư vấn của bạn. Chúng tôi sẽ phản hồi sớm.');
    }

    header('Location: ' . base_url('#lien-he'));
    exit;
}

$categories = fetch_all($conn, "SELECT cat.id,
                                      cat.name,
                                      cat.slug,
                                      cat.description,
                                      COUNT(course.id) AS total_courses
                               FROM categories cat
                               LEFT JOIN courses course
                                   ON course.category_id = cat.id
                                  AND course.status = 'published'
                               GROUP BY cat.id
                               ORDER BY total_courses DESC, cat.name ASC");

$stats = [
    'courses' => (int) (fetch_all($conn, "SELECT COUNT(*) AS total FROM courses WHERE status = 'published'")[0]['total'] ?? 0),
    'students' => (int) (fetch_all($conn, 'SELECT COUNT(*) AS total FROM students')[0]['total'] ?? 0),
    'enrollments' => (int) (fetch_all($conn, 'SELECT COUNT(*) AS total FROM enrollments')[0]['total'] ?? 0),
    'reviews' => (int) (fetch_all($conn, "SELECT COUNT(*) AS total FROM course_reviews WHERE status = 'approved'")[0]['total'] ?? 0),
];

$featuredSql = "SELECT c.id,
                       c.title,
                       c.subject,
                       c.description,
                       c.level,
                       c.teacher_name,
                       c.price,
                       c.thumbnail,
                       cat.name AS category_name,
                       cat.slug AS category_slug,
                       COUNT(DISTINCT e.id) AS total_enrollments,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(DISTINCT r.id) AS total_reviews
                FROM courses c
                LEFT JOIN categories cat ON cat.id = c.category_id
                LEFT JOIN enrollments e ON e.course_id = c.id
                LEFT JOIN course_reviews r ON r.course_id = c.id AND r.status = 'approved'
                WHERE c.status = 'published'";
$types = '';
$params = [];

if ($search !== '') {
    $keyword = '%' . $search . '%';
    $featuredSql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.teacher_name LIKE ? OR c.subject LIKE ?)";
    $types .= 'ssss';
    array_push($params, $keyword, $keyword, $keyword, $keyword);
}

if ($categorySlug !== '') {
    $featuredSql .= " AND cat.slug = ?";
    $types .= 's';
    $params[] = $categorySlug;
}

$featuredSql .= " GROUP BY c.id, cat.name, cat.slug
                  ORDER BY total_enrollments DESC, avg_rating DESC, c.created_at DESC
                  LIMIT 6";
$featuredStmt = $conn->prepare($featuredSql);
if ($types !== '') {
    $featuredStmt->bind_param($types, ...$params);
}
$featuredStmt->execute();
$featuredCourses = $featuredStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$featuredStmt->close();

$reviewRows = fetch_all(
    $conn,
    "SELECT r.rating, r.comment, r.created_at, u.name, c.title AS course_title
     FROM course_reviews r
     INNER JOIN users u ON u.id = r.user_id
     INNER JOIN courses c ON c.id = r.course_id
     WHERE r.status = 'approved'
     ORDER BY r.created_at DESC
     LIMIT 3"
);

$ctaLink = $user ? dashboard_url($user) : base_url('register.php');
$ctaText = $user ? 'Vào dashboard' : 'Tạo tài khoản';

render_header('PAT-Edu | Trung tâm học online', $user);
?>
<section class="homepage-section">
    <div class="hero-card mb-4">
        <div class="hero-layout">
            <div>
                <span class="hero-kicker mb-3">Trang chủ công khai trước khi đăng nhập</span>
                <h1 class="hero-title">Website học online chuyên nghiệp, rõ ràng và dễ dùng ngay từ lần đầu truy cập.</h1>
                <p class="hero-intro">
                    PAT-Edu được thiết kế theo hướng sản phẩm thật: có landing page trước khi login, danh sách khóa học rõ ràng,
                    lộ trình học cá nhân hóa bằng AI, dashboard riêng cho từng vai trò và luồng đăng ký phù hợp cho phụ huynh lẫn học sinh.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= e($ctaLink) ?>"><?= e($ctaText) ?></a>
                    <a class="btn btn-outline-primary btn-lg" href="<?= e(base_url('courses.php')) ?>">Xem tất cả khóa học</a>
                    <a class="btn btn-outline-secondary btn-lg" href="#lien-he">Nhận tư vấn</a>
                </div>
                <div class="hero-stat-grid">
                    <div class="hero-stat">
                        <div class="section-label mb-2">Khóa học</div>
                        <strong class="d-block fs-4"><?= e((string) $stats['courses']) ?></strong>
                        <div class="text-secondary">Đã xuất bản và sẵn sàng đăng ký</div>
                    </div>
                    <div class="hero-stat">
                        <div class="section-label mb-2">Học viên</div>
                        <strong class="d-block fs-4"><?= e((string) $stats['students']) ?></strong>
                        <div class="text-secondary">Đang học và được theo dõi tiến độ</div>
                    </div>
                    <div class="hero-stat">
                        <div class="section-label mb-2">Đăng ký</div>
                        <strong class="d-block fs-4"><?= e((string) $stats['enrollments']) ?></strong>
                        <div class="text-secondary">Lượt tham gia khóa học trên hệ thống</div>
                    </div>
                </div>
            </div>
            <div class="hero-panel">
                <div class="hero-summary-card">
                    <div class="section-label mb-2">Trải nghiệm đầu tiên</div>
                    <h2 class="h3 mb-3">Người dùng không cần mò mẫm.</h2>
                    <div class="hero-checklist">
                        <div class="hero-check-item">Tìm khóa học nhanh theo tên, giáo viên hoặc mục tiêu học tập.</div>
                        <div class="hero-check-item">Lọc theo danh mục thật lấy từ database, không phải demo cứng.</div>
                        <div class="hero-check-item">Phụ huynh có thể đăng ký và theo dõi con trong cùng một hệ thống.</div>
                        <div class="hero-check-item">AI hỗ trợ chấm bài, gợi ý học và phân tích điểm mạnh, điểm yếu.</div>
                    </div>
                </div>
                <div class="hero-mini-card-grid">
                    <div class="hero-mini-card">
                        <div class="section-label mb-2">Đánh giá</div>
                        <strong><?= e((string) $stats['reviews']) ?></strong>
                        <span class="text-secondary">nhận xét thực tế từ phụ huynh và học viên</span>
                    </div>
                    <div class="hero-mini-card">
                        <div class="section-label mb-2">AI Dashboard</div>
                        <strong>24/7</strong>
                        <span class="text-secondary">gợi ý luyện tập dựa trên kết quả học</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="homepage-section">
    <div class="search-panel">
        <div class="filter-toolbar mb-3">
            <div>
                <div class="section-label mb-2">Tìm kiếm khóa học</div>
                <h2 class="h3 mb-1">Tra cứu nhanh ngay trên trang chủ</h2>
                <p class="page-subtitle mb-0">Nhập từ khóa hoặc chọn danh mục để lọc các khóa học nổi bật bên dưới.</p>
            </div>
            <a class="result-badge" href="<?= e(base_url('courses.php')) ?>">Đi tới trang danh sách đầy đủ</a>
        </div>
        <form method="get" action="<?= e(base_url()) ?>">
            <div class="search-form-grid">
                <input
                    type="text"
                    class="form-control"
                    name="q"
                    value="<?= e($search) ?>"
                    placeholder="Ví dụ: Sinh học, toán tư duy, tiếng Anh..."
                >
                <select class="form-select" name="category">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Tìm khóa học</button>
            </div>
        </form>
    </div>
</section>

<section class="homepage-section" id="gioi-thieu">
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="feature-card">
                <div class="icon-badge mb-3">01</div>
                <h3 class="h5 mb-2">Homepage trước khi login</h3>
                <p class="text-secondary mb-0">Người truy cập nhìn thấy rõ hệ thống là gì, học gì và bắt đầu từ đâu trước khi tạo tài khoản.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="feature-card">
                <div class="icon-badge mb-3">02</div>
                <h3 class="h5 mb-2">Phân vai rõ ràng</h3>
                <p class="text-secondary mb-0">Admin, phụ huynh và học sinh có dashboard riêng, đúng nhu cầu sử dụng thực tế.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="feature-card">
                <div class="icon-badge mb-3">03</div>
                <h3 class="h5 mb-2">AI học tập</h3>
                <p class="text-secondary mb-0">Chấm bài, giải thích đáp án và gợi ý bài luyện tập dễ hơn hoặc nâng cao hơn.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="feature-card">
                <div class="icon-badge mb-3">04</div>
                <h3 class="h5 mb-2">Sẵn sàng mở rộng</h3>
                <p class="text-secondary mb-0">Cấu trúc rõ ràng, dễ bảo trì trên XAMPP và thuận lợi để nâng cấp thành API hoặc frontend mới sau này.</p>
            </div>
        </div>
    </div>
</section>

<section class="homepage-section" id="khoa-hoc">
    <div class="result-toolbar mb-4">
        <div class="section-heading">
            <div class="section-label mb-2">Khóa học nổi bật</div>
            <h2 class="h1 mb-2">Danh mục thật, dữ liệu thật, tìm kiếm thật</h2>
            <p class="page-subtitle mb-0">
                Homepage lấy trực tiếp danh sách danh mục và khóa học từ MySQL. Nếu bạn lọc hoặc tìm kiếm ở đây,
                kết quả hiển thị ngay trên landing page thay vì chỉ là giao diện mô phỏng.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($search !== '' || $categorySlug !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= e(base_url()) ?>">Xóa bộ lọc</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?= e(base_url('courses.php?q=' . urlencode($search) . '&category=' . urlencode($categorySlug))) ?>">Xem danh sách đầy đủ</a>
        </div>
    </div>

    <div class="section-chip-row mb-4">
        <a class="section-chip <?= $categorySlug === '' ? 'active' : '' ?>" href="<?= e(base_url()) ?>">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a
                class="section-chip <?= $categorySlug === $category['slug'] ? 'active' : '' ?>"
                href="<?= e(base_url('?category=' . urlencode((string) $category['slug']))) ?>"
            >
                <?= e($category['name']) ?>
                <span><?= e((string) $category['total_courses']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <?php if (empty($featuredCourses)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <h3 class="h4 mb-2">Chưa tìm thấy khóa học phù hợp</h3>
                    <p class="mb-0">Hãy thử từ khóa khác hoặc bỏ bộ lọc danh mục để xem thêm chương trình đang mở.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($featuredCourses as $course): ?>
                <div class="col-md-6 col-xl-4">
                    <article class="course-card">
                        <div class="course-visual">
                            <span class="course-visual-label"><?= e($course['category_name'] ?: $course['subject']) ?></span>
                            <div class="course-visual-title"><?= e($course['title']) ?></div>
                            <div class="course-meta">
                                <span class="course-pill"><?= e(level_label($course['level'])) ?></span>
                                <span class="course-pill"><?= e($course['teacher_name']) ?></span>
                            </div>
                        </div>
                        <div class="course-card-top">
                            <div class="course-rating">
                                <span class="rating-stars"><?= star_rating_markup((int) round((float) $course['avg_rating'])) ?></span>
                                <span><?= number_format((float) $course['avg_rating'], 1) ?>/5 · <?= (int) $course['total_reviews'] ?> đánh giá</span>
                            </div>
                            <div>
                                <h3 class="h4 mb-2"><?= e($course['title']) ?></h3>
                                <p class="text-secondary mb-0"><?= e(course_excerpt($course['description'])) ?></p>
                            </div>
                        </div>
                        <div class="course-footer pt-2">
                            <div>
                                <div class="course-price"><?= e(format_currency((float) $course['price'])) ?></div>
                                <div class="text-secondary small"><?= e((string) $course['total_enrollments']) ?> lượt đăng ký</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-primary" href="<?= e(base_url('course.php?id=' . (int) $course['id'])) ?>">Xem chi tiết</a>
                                <a class="btn btn-primary" href="<?= e($user ? dashboard_url($user) : base_url('register.php')) ?>">Đăng ký</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="homepage-section" id="trai-nghiem">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="stat-card h-100">
                <div class="section-label mb-2">Quy trình sử dụng</div>
                <h2 class="h2 mb-3">Luồng thao tác đơn giản cho người mới.</h2>
                <p class="text-secondary mb-0">
                    Chúng tôi chủ động rút gọn hành trình người dùng: vào trang chủ, xem khóa học, tạo tài khoản,
                    rồi đi thẳng đến dashboard đúng vai trò. Không có bước thừa, không buộc login ngay khi vừa truy cập.
                </p>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="process-card stat-card">
                        <div class="section-label mb-2">Bước 1</div>
                        <h3 class="h5 mb-2">Khám phá</h3>
                        <p class="text-secondary mb-0">Xem banner, bộ lọc, khóa học nổi bật và đánh giá để định hướng nhanh.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="process-card stat-card">
                        <div class="section-label mb-2">Bước 2</div>
                        <h3 class="h5 mb-2">Đăng ký</h3>
                        <p class="text-secondary mb-0">Tạo tài khoản phụ huynh hoặc đăng nhập để tiếp tục đăng ký khóa học cho con.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="process-card stat-card">
                        <div class="section-label mb-2">Bước 3</div>
                        <h3 class="h5 mb-2">Theo dõi</h3>
                        <p class="text-secondary mb-0">Xem lịch học, điểm số, AI feedback, thanh toán và thông báo ở dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="homepage-section">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="showcase-card h-100">
                <div class="section-label mb-2">AI cá nhân hóa</div>
                <h2 class="h2 mb-3">Không chỉ là website khóa học, mà là một hệ thống học tập thông minh.</h2>
                <p class="text-secondary mb-4">
                    Hệ thống phân tích điểm số, thời gian học và kết quả làm bài để đề xuất nội dung phù hợp.
                    Nếu học sinh sai nhiều, AI gợi ý bài dễ hơn. Nếu học tốt, AI mở sang bài nâng cao hoặc bài luyện tiếp theo.
                </p>
                <div class="hero-checklist">
                    <div class="hero-check-item">Trắc nghiệm được chấm ngay và có giải thích đáp án đúng hoặc sai.</div>
                    <div class="hero-check-item">Tự luận được nhận xét cơ bản dựa trên từ khóa và nội dung bài học.</div>
                    <div class="hero-check-item">Dashboard học sinh và phụ huynh hiển thị phân tích điểm mạnh, điểm yếu, cảnh báo học yếu.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row g-4">
                <div class="col-sm-6">
                    <div class="stat-card h-100">
                        <div class="section-label mb-2">Điểm mạnh</div>
                        <div class="stat-number">AI</div>
                        <p class="text-secondary mb-0">Tự gợi ý bài học phù hợp theo kết quả học tập thực tế.</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="stat-card h-100">
                        <div class="section-label mb-2">Dashboard</div>
                        <div class="stat-number">3 role</div>
                        <p class="text-secondary mb-0">Admin, phụ huynh và học sinh có màn hình riêng phù hợp với công việc của mình.</p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="showcase-card h-100">
                        <div class="section-label mb-2">Vì sao cách làm này tốt hơn?</div>
                        <p class="text-secondary mb-0">
                            Nhiều website e-learning nhỏ thường đẩy người dùng vào login ngay và mất luôn khả năng thuyết phục.
                            PAT-Edu đi ngược lại: ưu tiên landing page rõ ràng, sau đó mới kéo người dùng vào đăng ký khi họ đã hiểu hệ thống.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="homepage-section" id="danh-gia">
    <div class="result-toolbar mb-4">
        <div class="section-heading">
            <div class="section-label mb-2">Cảm nhận người dùng</div>
            <h2 class="h1 mb-2">Đánh giá giúp homepage có chiều sâu hơn</h2>
            <p class="page-subtitle mb-0">Phần này đang lấy trực tiếp review từ database để tăng độ tin cậy cho landing page.</p>
        </div>
    </div>
    <div class="row g-4">
        <?php foreach ($reviewRows as $review): ?>
            <div class="col-lg-4">
                <div class="review-card h-100">
                    <div class="review-meta">
                        <div>
                            <strong><?= e($review['name']) ?></strong>
                            <div class="text-secondary small"><?= e($review['course_title']) ?></div>
                        </div>
                        <div class="rating-stars"><?= star_rating_markup((int) $review['rating']) ?></div>
                    </div>
                    <p class="mb-3"><?= e(course_excerpt($review['comment'], 180)) ?></p>
                    <div class="text-secondary small"><?= e((string) $review['created_at']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="homepage-section" id="lien-he">
    <div class="footer-block">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="section-label text-white-50 mb-2">Liên hệ tư vấn</div>
                <h2 class="h1 mb-3 text-white">Bắt đầu với PAT-Edu theo cách rõ ràng và chuyên nghiệp hơn.</h2>
                <p class="mb-4">
                    Đây là form hoạt động thật, lưu dữ liệu vào bảng <strong>contact_messages</strong>.
                    Phụ huynh có thể để lại nhu cầu tư vấn, chọn khóa học phù hợp hoặc hỏi về lịch học.
                </p>
                <div class="d-grid gap-2">
                    <div>Email: support@pat-edu.local</div>
                    <div>Hotline: 0909 000 001</div>
                    <div>Địa chỉ demo: localhost / elearning / pat_edu</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="search-panel bg-white">
                    <form method="post">
                        <input type="hidden" name="action" value="contact_message">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên</label>
                                <input class="form-control" name="name" value="<?= e($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <input class="form-control" name="phone" placeholder="0909 xxx xxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Chủ đề</label>
                                <input class="form-control" name="subject" placeholder="Ví dụ: Tư vấn khóa học lớp 7" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nội dung</label>
                                <textarea class="form-control" name="message" rows="4" placeholder="Mô tả nhu cầu của bạn để đội ngũ hỗ trợ phản hồi nhanh hơn." required></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                            <div class="text-secondary">Sau khi gửi, admin có thể xem yêu cầu này trong phần quản trị.</div>
                            <button class="btn btn-primary" type="submit">Gửi yêu cầu tư vấn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
