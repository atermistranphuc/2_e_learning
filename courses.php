<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/auth.php';

$user = current_user();
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'popular');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$sortMap = [
    'popular' => 'total_enrollments DESC, avg_rating DESC, c.created_at DESC',
    'newest' => 'c.created_at DESC',
    'price_low' => 'c.price ASC, c.created_at DESC',
    'price_high' => 'c.price DESC, c.created_at DESC',
    'rating' => 'avg_rating DESC, total_reviews DESC, c.created_at DESC',
];

if (!isset($sortMap[$sort])) {
    $sort = 'popular';
}

$categories = fetch_all($conn, "SELECT cat.id,
                                      cat.name,
                                      cat.slug,
                                      COUNT(course.id) AS total_courses
                               FROM categories cat
                               LEFT JOIN courses course
                                   ON course.category_id = cat.id
                                  AND course.status = 'published'
                               GROUP BY cat.id
                               ORDER BY cat.name ASC");

$joinSql = " FROM courses c
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN enrollments e ON e.course_id = c.id
             LEFT JOIN course_reviews r ON r.course_id = c.id AND r.status = 'approved'
             WHERE c.status = 'published'";
$types = '';
$params = [];

if ($search !== '') {
    $keyword = '%' . $search . '%';
    $joinSql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.teacher_name LIKE ? OR c.subject LIKE ?)";
    $types .= 'ssss';
    array_push($params, $keyword, $keyword, $keyword, $keyword);
}

if ($categorySlug !== '') {
    $joinSql .= ' AND cat.slug = ?';
    $types .= 's';
    $params[] = $categorySlug;
}

$countSql = 'SELECT COUNT(DISTINCT c.id) AS total' . $joinSql;
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$listSql = "SELECT c.id,
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
                   COUNT(DISTINCT r.id) AS total_reviews"
          . $joinSql .
          " GROUP BY c.id, cat.name, cat.slug
            ORDER BY {$sortMap[$sort]}
            LIMIT ? OFFSET ?";
$listStmt = $conn->prepare($listSql);
$listTypes = $types . 'ii';
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$courses = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

render_header('Danh sách khóa học | PAT-Edu', $user);
?>
<section class="homepage-section">
    <div class="dashboard-hero mb-4">
        <div class="result-toolbar">
            <div class="section-heading">
                <div class="section-label mb-2">Khóa học</div>
                <h1 class="h1 mb-2">Thư viện khóa học dành cho phụ huynh và học sinh</h1>
                <p class="page-subtitle mb-0">
                    Trang này dành cho bước khám phá sâu hơn sau homepage: có tìm kiếm, lọc theo danh mục, sắp xếp và phân trang.
                    Mọi dữ liệu đều đang lấy thật từ MySQL, không phải danh sách tĩnh.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="result-badge"><?= e((string) $totalRows) ?> khóa học phù hợp</span>
                <a class="btn btn-outline-primary" href="<?= e(base_url()) ?>">Về trang chủ</a>
            </div>
        </div>
    </div>
</section>

<section class="homepage-section">
    <div class="search-panel mb-4">
        <form method="get">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label">Từ khóa</label>
                    <input class="form-control" type="text" name="q" value="<?= e($search) ?>" placeholder="Tìm theo tên khóa học, giáo viên, mô tả...">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Danh mục</label>
                    <select class="form-select" name="category">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?> (<?= e((string) $category['total_courses']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" name="sort">
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Phổ biến nhất</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá cao</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                    </select>
                </div>
                <div class="col-lg-2 d-grid">
                    <button class="btn btn-primary">Lọc kết quả</button>
                </div>
            </div>
        </form>
    </div>

    <div class="section-chip-row mb-4">
        <a class="section-chip <?= $categorySlug === '' ? 'active' : '' ?>" href="<?= e(base_url('courses.php?q=' . urlencode($search) . '&sort=' . urlencode($sort))) ?>">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a class="section-chip <?= $categorySlug === $category['slug'] ? 'active' : '' ?>" href="<?= e(base_url('courses.php?q=' . urlencode($search) . '&category=' . urlencode((string) $category['slug']) . '&sort=' . urlencode($sort))) ?>">
                <?= e($category['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <?php if (empty($courses)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <h2 class="h4 mb-2">Không có khóa học phù hợp với bộ lọc hiện tại</h2>
                    <p class="mb-3">Hãy thử bỏ bớt từ khóa, chuyển sang danh mục khác hoặc quay về homepage để xem các khóa học nổi bật.</p>
                    <a class="btn btn-primary" href="<?= e(base_url('courses.php')) ?>">Xem lại toàn bộ khóa học</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="col-md-6 col-xl-4">
                    <article class="course-card h-100">
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
                                <p class="text-secondary mb-0"><?= e(course_excerpt($course['description'], 145)) ?></p>
                            </div>
                        </div>
                        <div class="course-footer pt-2">
                            <div>
                                <div class="course-price"><?= e(format_currency((float) $course['price'])) ?></div>
                                <div class="text-secondary small"><?= e((string) $course['total_enrollments']) ?> lượt đăng ký</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-primary" href="<?= e(base_url('course.php?id=' . (int) $course['id'])) ?>">Chi tiết</a>
                                <a class="btn btn-primary" href="<?= e($user ? dashboard_url($user) : base_url('register.php')) ?>">Đăng ký</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(base_url('courses.php?q=' . urlencode($search) . '&category=' . urlencode($categorySlug) . '&sort=' . urlencode($sort) . '&page=' . $i)) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
