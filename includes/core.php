<?php
declare(strict_types=1);

function app_config(): array {
    return [
        'db_host' => getenv('DB_HOST') ?: 'localhost',
        'db_name' => getenv('DB_NAME') ?: 'wedlock',
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_pass' => getenv('DB_PASS') ?: '',
        'cache_ttl' => 300,
    ];
}

function app_storage_path(string $name = ''): string {
    $base = __DIR__ . '/../storage';
    if (!is_dir($base)) {
        @mkdir($base, 0775, true);
    }
    if ($name !== '') {
        $child = $base . '/' . $name;
        if (!is_dir($child)) {
            @mkdir($child, 0775, true);
        }
        return $child;
    }
    return $base;
}

function pdo_conn(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $c = app_config();
    $dsn = "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function ensure_schema(PDO $pdo): void {
    $sql = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            phone VARCHAR(20) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user','admin') NOT NULL DEFAULT 'user',
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            rejection_reason VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_status(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS profiles (
            user_id INT PRIMARY KEY,
            gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other',
            seeking_gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other',
            dob DATE NULL,
            city VARCHAR(80) NULL,
            religion VARCHAR(80) NULL,
            education VARCHAR(120) NULL,
            occupation VARCHAR(120) NULL,
            annual_income_lpa DECIMAL(8,2) NULL,
            about_me TEXT NULL,
            bio TEXT NULL,
            profile_photo_url VARCHAR(255) NULL,
            cover_photo_url VARCHAR(255) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_profile_gender(gender),
            INDEX idx_profile_city(city)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_code VARCHAR(40) NOT NULL UNIQUE,
            plan_name VARCHAR(80) NOT NULL,
            price_inr DECIMAL(10,2) NOT NULL DEFAULT 0,
            duration_days INT NOT NULL DEFAULT 30,
            max_contact_views INT NOT NULL DEFAULT 10,
            has_priority_listing TINYINT(1) NOT NULL DEFAULT 0,
            has_advanced_filters TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
            started_at DATETIME NOT NULL DEFAULT NOW(),
            expires_at DATETIME NOT NULL,
            payment_ref VARCHAR(120) NULL,
            CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
            INDEX idx_sub_user(user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS interests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT NOT NULL,
            to_user_id INT NOT NULL,
            status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_interest(from_user_id, to_user_id),
            INDEX idx_interest_to(to_user_id, status),
            CONSTRAINT fk_i_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_i_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS profile_vectors (
            user_id INT PRIMARY KEY,
            cluster_id INT NULL,
            v1 DECIMAL(8,4) NOT NULL DEFAULT 0,
            v2 DECIMAL(8,4) NOT NULL DEFAULT 0,
            v3 DECIMAL(8,4) NOT NULL DEFAULT 0,
            v4 DECIMAL(8,4) NOT NULL DEFAULT 0,
            CONSTRAINT fk_vec_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_vec_cluster(cluster_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(80) PRIMARY KEY,
            setting_value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($sql as $q) $pdo->exec($q);

    $plans = (int)$pdo->query("SELECT COUNT(*) c FROM plans")->fetch()['c'];
    if ($plans === 0) {
        $pdo->exec("INSERT INTO plans (plan_code, plan_name, price_inr, duration_days, max_contact_views, has_priority_listing, has_advanced_filters, is_active, sort_order) VALUES
            ('free','Free',0,3650,8,0,0,1,1),
            ('gold','Gold',999,30,60,1,1,1,2),
            ('platinum','Platinum',2499,90,999,1,1,1,3)");
    }
    $admins = (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch()['c'];
    if ($admins === 0) {
        $hash = password_hash('admin123456', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users(full_name,email,phone,password_hash,role,status) VALUES('Super Admin','admin@wedlock.local','9999999999',?,'admin','approved')")->execute([$hash]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO profiles(user_id,bio,about_me,gender,seeking_gender) VALUES(?, 'Platform admin','Handles approvals and plans','Other','Other')")->execute([$id]);
    }
}

function current_user(PDO $pdo): ?array {
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid <= 0) return null;
    $stmt = $pdo->prepare("SELECT u.*, p.gender, p.seeking_gender, p.city, p.bio, p.about_me, p.profile_photo_url FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
    $stmt->execute([$uid]);
    return $stmt->fetch() ?: null;
}

function require_auth(PDO $pdo): array {
    $u = current_user($pdo);
    if (!$u) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    return $u;
}

function require_admin(PDO $pdo): array {
    $u = require_auth($pdo);
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
    return $u;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function cache_get(string $k): mixed {
    $f = app_storage_path('cache') . '/' . md5($k) . '.json';
    if (!file_exists($f)) return null;
    $x = json_decode((string)file_get_contents($f), true);
    if (!is_array($x) || time() > (int)($x['exp'] ?? 0)) { @unlink($f); return null; }
    return $x['v'] ?? null;
}

function cache_set(string $k, mixed $v, int $ttl): void {
    $f = app_storage_path('cache') . '/' . md5($k) . '.json';
    file_put_contents($f, json_encode(['exp' => time() + $ttl, 'v' => $v]));
}

function age_from(?string $dob): ?int {
    if (!$dob) return null;
    try { return (int)(new DateTime($dob))->diff(new DateTime('today'))->y; } catch (Throwable) { return null; }
}

function profile_vec(array $p): array {
    $age = age_from($p['dob'] ?? null); $ageN = $age ? max(0, min(1, ($age - 18) / 35)) : 0.5;
    $incomeN = max(0, min(1, ((float)($p['annual_income_lpa'] ?? 0)) / 60));
    $edu = strtolower((string)($p['education'] ?? ''));
    $eduN = str_contains($edu, 'master') ? 0.8 : (str_contains($edu, 'bachelor') ? 0.6 : 0.4);
    $cityN = (crc32(strtolower((string)($p['city'] ?? 'x'))) % 1000) / 1000;
    return [$ageN, $incomeN, $eduN, $cityN];
}

function dist(array $a, array $b): float {
    $s = 0.0; for ($i = 0; $i < count($a); $i++) { $d = $a[$i]-$b[$i]; $s += $d*$d; } return sqrt($s);
}

function run_kmeans(PDO $pdo): void {
    $rows = $pdo->query("SELECT p.user_id,p.dob,p.annual_income_lpa,p.education,p.city FROM profiles p INNER JOIN users u ON u.id=p.user_id WHERE u.status='approved'")->fetchAll();
    if (count($rows) < 4) return;
    $vecs = []; foreach ($rows as $r) $vecs[(int)$r['user_id']] = profile_vec($r);
    $ids = array_keys($vecs); $k = min(4, count($vecs)); $c = [];
    for ($i = 0; $i < $k; $i++) $c[$i] = $vecs[$ids[$i]];
    $asg = [];
    for ($it = 0; $it < 6; $it++) {
        $groups = array_fill(0, $k, []);
        foreach ($vecs as $uid => $v) {
            $best = 0; $bestD = 9e9;
            for ($j = 0; $j < $k; $j++) { $d = dist($v, $c[$j]); if ($d < $bestD) { $bestD = $d; $best = $j; } }
            $asg[$uid] = $best; $groups[$best][] = $v;
        }
        for ($j = 0; $j < $k; $j++) {
            if (!$groups[$j]) continue;
            $avg = [0,0,0,0];
            foreach ($groups[$j] as $g) for ($m = 0; $m < 4; $m++) $avg[$m] += $g[$m];
            for ($m = 0; $m < 4; $m++) $avg[$m] /= count($groups[$j]);
            $c[$j] = $avg;
        }
    }
    $st = $pdo->prepare("INSERT INTO profile_vectors(user_id,cluster_id,v1,v2,v3,v4) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE cluster_id=VALUES(cluster_id),v1=VALUES(v1),v2=VALUES(v2),v3=VALUES(v3),v4=VALUES(v4)");
    foreach ($vecs as $uid => $v) $st->execute([$uid, $asg[$uid], $v[0], $v[1], $v[2], $v[3]]);
}

