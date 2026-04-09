<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

require_login();
redirect_by_role(current_user()['role']);
