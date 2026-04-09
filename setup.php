<?php
declare(strict_types=1);

require __DIR__ . '/pages/setup_page.php';
return;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'pat_edu';
$message = null;
$messageType = 'info';

$serverConn = @new mysqli($dbHost, $dbUser, $dbPass);
$serverOk = !$serverConn->connect_error;
$databaseExists = false;
$tables = [];

if ($serverOk) {
    $serverConn->set_charset('utf8mb4');
    $checkDb = $serverConn->query("SHOW DATABASES LIKE '{$dbName}'");
    $databaseExists = $checkDb && $checkDb->num_rows > 0;

    if ($databaseExists) {
        $serverConn->select_db($dbName);
        $tableResult = $serverConn->query('SHOW TABLES');
        if ($tableResult) {
            while ($row = $tableResult->fetch_array()) {
                $tables[] = $row[0];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $serverOk) {
    $sqlFile = __DIR__ . '/database/pat_edu.sql';
    if (is_file($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if ($sql !== false && $serverConn->multi_query($sql)) {
            do {
                if ($result = $serverConn->store_result()) {
                    $result->free();
                }
            } while ($serverConn->more_results() && $serverConn->next_result());

            $message = 'Da cai dat database thanh cong. Ban co the vao website ngay.';
            $messageType = 'success';
            $databaseExists = true;
            $serverConn->select_db($dbName);
            $tables = [];
            $tableResult = $serverConn->query('SHOW TABLES');
            if ($tableResult) {
                while ($row = $tableResult->fetch_array()) {
                    $tables[] = $row[0];
                }
            }
        } else {
            $message = 'Khong the import SQL. Loi: ' . $serverConn->error;
            $messageType = 'danger';
        }
    } else {
        $message = 'Khong tim thay file database/pat_edu.sql';
        $messageType = 'danger';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PAT-Edu Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3f7fb; }
        .card-box { border-radius: 18px; border: 1px solid #dbe4f0; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-box bg-white p-4 p-lg-5">
                <h1 class="h2 mb-3">PAT-Edu Setup</h1>
                <p class="text-secondary">Trang nay giup ban khoi dong du an tren XAMPP thay vi mo file PHP truc tiep trong editor.</p>

                <?php if ($message): ?>
                    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="fw-semibold mb-2">Trang thai moi truong</div>
                    <ul class="mb-0">
                        <li>Apache: ban hay bat trong XAMPP Control Panel</li>
                        <li>MySQL ket noi: <strong><?= $serverOk ? 'OK' : 'Chua ket noi duoc' ?></strong></li>
                        <li>Database `pat_edu`: <strong><?= $databaseExists ? 'Da ton tai' : 'Chua ton tai' ?></strong></li>
                        <li>So bang hien co: <strong><?= count($tables) ?></strong></li>
                    </ul>
                </div>

                <?php if (!$serverOk): ?>
                    <div class="alert alert-warning">Chua ket noi duoc MySQL. Ban hay bat MySQL trong XAMPP roi tai lai trang nay.</div>
                <?php else: ?>
                    <form method="post" class="mb-4">
                        <button class="btn btn-primary">Tu dong cai dat database</button>
                    </form>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="fw-semibold mb-2">Cach chay dung</div>
                    <ol class="mb-0">
                        <li>Bat `Apache` va `MySQL` trong XAMPP.</li>
                        <li>Mo trang nay: <code>http://localhost/elearning/pat_edu/setup.php</code></li>
                        <li>Bam <code>Tu dong cai dat database</code>.</li>
                        <li>Vao <a href="/elearning/pat_edu/">http://localhost/elearning/pat_edu/</a>.</li>
                    </ol>
                </div>

                <div class="mb-4">
                    <div class="fw-semibold mb-2">Tai khoan mau</div>
                    <ul class="mb-0">
                        <li>Admin: <code>admin@pat.edu</code> / <code>password</code></li>
                        <li>Parent: <code>parent1@pat.edu</code> / <code>password</code></li>
                        <li>Student: <code>student1@pat.edu</code> / <code>password</code></li>
                    </ul>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-success" href="/elearning/pat_edu/">Mo website</a>
                    <a class="btn btn-outline-secondary" href="/elearning/pat_edu/login.php">Trang dang nhap</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
