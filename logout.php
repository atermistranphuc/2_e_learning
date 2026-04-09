<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/auth.php';

logout_user();
set_flash('success', 'Bạn đã đăng xuất khỏi hệ thống.');
header('Location: ' . base_url('login.php'));
exit;
