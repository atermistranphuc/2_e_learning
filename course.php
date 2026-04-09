<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/auth.php';

$user = current_user();
$courseId = (int) ($_GET['id'] ?? 0);
if ($courseId <= 0) {
    header('Location: ' . base_url('courses.php'));
    exit;
}

$courseStmt = $conn->prepare("SELECT c.id,
                                    c.title,
                                    c.subject,
                                    c.description,
                                    c.level,
                                    c.category_id,
                                    c.teacher_name,
                                    c.price,
                                    c.thumbnail,
                                    c.created_at,
                                    cat.name AS category_name,
                                    cat.slug AS category_slug,
                                    COUNT(DISTINCT e.id) AS total_enrollments,
                                    COUNT(DISTINCT l.id) AS total_lessons,
                                    COALESCE(AVG(r.rating), 0) AS avg_rating,
                                    COUNT(DISTINCT r.id) AS total_reviews
                             FROM courses c
                             LEFT JOIN categories cat ON cat.id = c.category_id
                             LEFT JOIN enrollments e ON e.course_id = c.id
                             LEFT JOIN lessons l ON l.course_id = c.id
                             LEFT JOIN course_reviews r ON r.course_id = c.id AND r.status = 'approved'
                             WHERE c.id = ? AND c.status = 'published'
                             GROUP BY c.id, cat.name, cat.slug");
$courseStmt->bind_param('i', $courseId);
$courseStmt->execute();
$course = $courseStmt->get_result()->fetch_assoc();
$courseStmt->close();

if (!$course) {
    header('Location: ' . base_url('courses.php'));
    exit;
}

$lessonsStmt = $conn->prepare('SELECT id, title, lesson_order, lesson_type, difficulty_level, content FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC');
$lessonsStmt->bind_param('i', $courseId);
$lessonsStmt->execute();
$lessons = $lessonsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lessonsStmt->close();

$reviewsStmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.name
                               FROM course_reviews r
                               INNER JOIN users u ON u.id = r.user_id
                               WHERE r.course_id = ? AND r.status = 'approved'
                               ORDER BY r.created_at DESC");
$reviewsStmt->bind_param('i', $courseId);
$reviewsStmt->execute();
$reviews = $reviewsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reviewsStmt->close();

$relatedStmt = $conn->prepare("SELECT c.id,
                                      c.title,
                                      c.subject,
                                      c.level,
                                      c.teacher_name,
                                      c.price,
                                      cat.name AS category_name
                               FROM courses c
                               LEFT JOIN categories cat ON cat.id = c.category_id
                               WHERE c.status = 'published' AND c.id != ? AND c.category_id <=> ?
                               ORDER BY c.created_at DESC
                               LIMIT 3");
$categoryId = $course['category_id'] !== null ? (int) $course['category_id'] : null;
$relatedStmt->bind_param('ii', $courseId, $categoryId);
$relatedStmt->execute();
$relatedCourses = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$relatedStmt->close();

$isWished = false;
if ($user) {
    $wishStmt = $conn->prepare('SELECT id FROM wishlist_items WHERE user_id = ? AND course_id = ? LIMIT 1');
    $wishUserId = (int) $user['id'];
    $wishStmt->bind_param('ii', $wishUserId, $courseId);
    $wishStmt->execute();
    $isWished = (bool) $wishStmt->get_result()->fetch_assoc();
    $wishStmt->close();
}

$enrollActionLink = $user ? dashboard_url($user) : base_url('register.php');
$enrollActionText = $user ? 'Tiếp tục trong dashboard' : 'Tạo tài khoản để đăng ký';

render_header('Chi tiết khóa học | PAT-Edu', $user);
?>
<section class="homepage-section">
    <div class="hero-card mb-4">
        <div class="detail-hero-grid">
            <div>
                <span class="hero-kicker mb-3"><?= e($course['category_name'] ?: $course['subject']) ?> · <?= e(level_label($course['level'])) ?></span>
                <h1 class="hero-title mb-3"><?= e($course['title']) ?></h1>
                <p class="hero-intro mb-4"><?= e($course['description']) ?></p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= e($enrollActionLink) ?>"><?= e($enrollActionText) ?></a>
                    <a class="btn btn-outline-primary btn-lg" href="<?= e(base_url('courses.php')) ?>">Quay lại danh sách</a>
                    <?php if ($user): ?>
                        <button class="btn <?= $isWished ? 'btn-primary' : 'btn-outline-secondary' ?> btn-lg" id="wishlistBtn" data-course-id="<?= (int) $courseId ?>"><?= $isWished ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' ?></button>
                    <?php endif; ?>
                </div>
                <div class="hero-stat-grid mt-3">
                    <div class="hero-stat">
                        <div class="section-label mb-2">Giảng viên</div>
                        <strong class="d-block"><?= e($course['teacher_name']) ?></strong>
                        <div class="text-secondary">Theo sát tiến độ học viên</div>
                    </div>
                    <div class="hero-stat">
                        <div class="section-label mb-2">Bài học</div>
                        <strong class="d-block"><?= e((string) $course['total_lessons']) ?></strong>
                        <div class="text-secondary">Bao gồm video, quiz và luyện tập</div>
                    </div>
                    <div class="hero-stat">
                        <div class="section-label mb-2">Đăng ký</div>
                        <strong class="d-block"><?= e((string) $course['total_enrollments']) ?></strong>
                        <div class="text-secondary">Lượt học viên đang tham gia</div>
                    </div>
                </div>
            </div>
            <aside class="sidebar-card">
                <div class="section-label mb-2">Tổng quan khóa học</div>
                <div class="info-list">
                    <div class="info-item"><span>Học phí</span><strong><?= e(format_currency((float) $course['price'])) ?></strong></div>
                    <div class="info-item"><span>Danh mục</span><strong><?= e($course['category_name'] ?: $course['subject']) ?></strong></div>
                    <div class="info-item"><span>Trình độ</span><strong><?= e(level_label($course['level'])) ?></strong></div>
                    <div class="info-item"><span>Đánh giá</span><strong><?= number_format((float) $course['avg_rating'], 1) ?>/5</strong></div>
                    <div class="info-item"><span>Lượt review</span><strong><?= (int) $course['total_reviews'] ?></strong></div>
                </div>
                <div class="course-rating mt-4">
                    <span class="rating-stars"><?= star_rating_markup((int) round((float) $course['avg_rating'])) ?></span>
                    <span><?= number_format((float) $course['avg_rating'], 1) ?>/5 từ <?= (int) $course['total_reviews'] ?> đánh giá</span>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="homepage-section">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="showcase-card mb-4">
                <div class="section-label mb-2">Lộ trình học</div>
                <h2 class="h3 mb-3">Những gì học sinh sẽ trải qua trong khóa này</h2>
                <?php if (empty($lessons)): ?>
                    <div class="empty-state">Khóa học đang được cập nhật nội dung.</div>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="syllabus-item">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div>
                                    <strong>Bài <?= (int) $lesson['lesson_order'] ?>: <?= e($lesson['title']) ?></strong>
                                    <div class="text-secondary small mt-1"><?= e(ucfirst((string) $lesson['lesson_type'])) ?> · <?= e(level_label($lesson['difficulty_level'])) ?></div>
                                </div>
                                <span class="course-pill"><?= e(ucfirst((string) $lesson['lesson_type'])) ?></span>
                            </div>
                            <?php if (!empty($lesson['content'])): ?>
                                <div class="text-secondary"><?= e(course_excerpt($lesson['content'], 170)) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="showcase-card">
                <div class="result-toolbar mb-3">
                    <div>
                        <div class="section-label mb-2">Đánh giá & bình luận</div>
                        <h2 class="h3 mb-0">Cảm nhận từ người dùng</h2>
                    </div>
                    <span class="result-badge"><?= (int) $course['total_reviews'] ?> đánh giá</span>
                </div>

                <?php if ($user): ?>
                    <div class="review-card mb-4">
                        <form id="reviewForm">
                            <input type="hidden" name="course_id" value="<?= (int) $courseId ?>">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Số sao</label>
                                    <select class="form-select" name="rating" required>
                                        <option value="">Chọn</option>
                                        <option value="5">5 sao</option>
                                        <option value="4">4 sao</option>
                                        <option value="3">3 sao</option>
                                        <option value="2">2 sao</option>
                                        <option value="1">1 sao</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Chia sẻ cảm nhận</label>
                                    <textarea class="form-control" name="comment" rows="3" placeholder="Bạn thấy khóa học này hữu ích ở điểm nào?" required></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                                <div id="reviewMessage" class="text-secondary small">Đánh giá sẽ xuất hiện ngay sau khi gửi.</div>
                                <button class="btn btn-primary" type="submit">Gửi đánh giá</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="review-card mb-4">
                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <div>
                                <strong>Đăng nhập để đánh giá khóa học</strong>
                                <div class="text-secondary small">Phần review hoạt động bằng AJAX và lưu thật vào database.</div>
                            </div>
                            <a class="btn btn-primary" href="<?= e(base_url('login.php')) ?>">Đăng nhập</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="reviewList" class="d-grid gap-3">
                    <?php if (empty($reviews)): ?>
                        <div class="empty-state">Chưa có đánh giá nào cho khóa học này.</div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-meta">
                                    <div>
                                        <strong><?= e($review['name']) ?></strong>
                                        <div class="text-secondary small"><?= e((string) $review['created_at']) ?></div>
                                    </div>
                                    <div class="rating-stars"><?= star_rating_markup((int) $review['rating']) ?></div>
                                </div>
                                <p class="mb-0"><?= e($review['comment']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="sidebar-card mb-4">
                <div class="section-label mb-2">Dành cho ai?</div>
                <div class="hero-checklist">
                    <div class="hero-check-item">Phụ huynh muốn đăng ký và theo dõi tiến độ cho con.</div>
                    <div class="hero-check-item">Học sinh cần học online với lộ trình rõ ràng, ít rối mắt.</div>
                    <div class="hero-check-item">Trung tâm muốn quản lý nội dung, thanh toán, lịch học và báo cáo.</div>
                </div>
            </div>

            <div class="sidebar-card mb-4">
                <div class="section-label mb-2">API mở rộng</div>
                <p class="text-secondary mb-3">Mỗi khóa học có sẵn endpoint JSON để sau này dễ nâng cấp lên mobile app hoặc frontend tách riêng.</p>
                <a class="btn btn-outline-primary w-100" href="<?= e(base_url('api/courses.php?id=' . $courseId)) ?>" target="_blank">Mở JSON của khóa học</a>
            </div>

            <div class="sidebar-card">
                <div class="section-label mb-2">Khóa học liên quan</div>
                <div class="d-grid gap-3">
                    <?php if (empty($relatedCourses)): ?>
                        <div class="text-secondary">Chưa có khóa học liên quan trong cùng danh mục.</div>
                    <?php else: ?>
                        <?php foreach ($relatedCourses as $item): ?>
                            <div class="lesson-box">
                                <strong class="d-block mb-1"><?= e($item['title']) ?></strong>
                                <div class="text-secondary small mb-2"><?= e($item['category_name'] ?: $item['subject']) ?> · <?= e(level_label($item['level'])) ?></div>
                                <div class="course-footer">
                                    <div class="course-price"><?= e(format_currency((float) $item['price'])) ?></div>
                                    <a class="btn btn-outline-primary" href="<?= e(base_url('course.php?id=' . (int) $item['id'])) ?>">Xem</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="<?= e(base_url('assets/js/public.js')) ?>"></script>
<?php render_footer(); ?>
