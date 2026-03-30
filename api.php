<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/core.php';

header('Content-Type: application/json; charset=utf-8');
$pdo = pdo_conn();
ensure_schema($pdo);
$api = (string)($_GET['api'] ?? '');
$cfg = app_config();

function json_in(): array { return json_decode((string)file_get_contents('php://input'), true) ?: []; }

if ($api === 'matches') {
    $u = require_auth($pdo);
    $cacheKey = "m_{$u['id']}";
    $cached = cache_get($cacheKey);
    if (is_array($cached)) { echo json_encode(['ok' => true, 'items' => $cached]); exit; }
    run_kmeans($pdo);
    $me = $pdo->prepare("SELECT p.*,u.status FROM profiles p INNER JOIN users u ON u.id=p.user_id WHERE p.user_id=?");
    $me->execute([(int)$u['id']]);
    $m = $me->fetch();
    if (!$m) { echo json_encode(['ok' => true, 'items' => []]); exit; }
    $mv = profile_vec($m);
    $cid = (int)($pdo->prepare("SELECT cluster_id FROM profile_vectors WHERE user_id=?")->execute([(int)$u['id']]) ?: 0);
    $sql = "SELECT u.id,u.full_name,p.* FROM users u INNER JOIN profiles p ON p.user_id=u.id WHERE u.status='approved' AND u.id<>?";
    $params = [(int)$u['id']];
    if (($m['seeking_gender'] ?? 'Other') !== 'Other') { $sql .= " AND p.gender=?"; $params[] = $m['seeking_gender']; }
    $sql .= " ORDER BY u.id DESC LIMIT 100";
    $st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $v = profile_vec($r);
        $score = max(45, min(98, 100 - (int)round(dist($mv, $v) * 34)));
        $out[] = [
            'user_id' => (int)$r['id'],
            'full_name' => $r['full_name'],
            'age' => age_from($r['dob']),
            'city' => $r['city'],
            'religion' => $r['religion'],
            'education' => $r['education'],
            'occupation' => $r['occupation'],
            'bio' => (string)($r['bio'] ?? ''),
            'photo_url' => (string)($r['profile_photo_url'] ?? ''),
            'score' => $score,
        ];
    }
    usort($out, fn($a,$b) => $b['score'] <=> $a['score']);
    $out = array_slice($out, 0, 40);
    cache_set($cacheKey, $out, (int)$cfg['cache_ttl']);
    echo json_encode(['ok' => true, 'items' => $out]); exit;
}

if ($api === 'dashboard') {
    $u = require_auth($pdo); $uid = (int)$u['id'];
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) c FROM interests WHERE to_user_id=? AND status='pending'"); $pendingStmt->execute([$uid]);
    $pending = (int)($pendingStmt->fetch()['c'] ?? 0);
    $planStmt = $pdo->prepare("SELECT p.plan_name FROM subscriptions s INNER JOIN plans p ON p.id=s.plan_id WHERE s.user_id=? AND s.status='active' AND s.expires_at > NOW() ORDER BY s.id DESC LIMIT 1");
    $planStmt->execute([$uid]); $plan = $planStmt->fetch()['plan_name'] ?? 'Free';
    echo json_encode(['ok' => true, 'pending_requests' => $pending, 'active_plan' => $plan]); exit;
}

if ($api === 'save_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = require_auth($pdo); $uid = (int)$u['id']; $in = json_in();
    $st = $pdo->prepare("INSERT INTO profiles(user_id,gender,seeking_gender,dob,city,religion,education,occupation,annual_income_lpa,about_me,bio,profile_photo_url,cover_photo_url)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE gender=VALUES(gender),seeking_gender=VALUES(seeking_gender),dob=VALUES(dob),city=VALUES(city),religion=VALUES(religion),
        education=VALUES(education),occupation=VALUES(occupation),annual_income_lpa=VALUES(annual_income_lpa),about_me=VALUES(about_me),bio=VALUES(bio),
        profile_photo_url=VALUES(profile_photo_url),cover_photo_url=VALUES(cover_photo_url)");
    $st->execute([$uid,$in['gender'] ?? 'Other',$in['seeking_gender'] ?? 'Other',$in['dob'] ?: null,$in['city'] ?? null,$in['religion'] ?? null,$in['education'] ?? null,$in['occupation'] ?? null,
        !empty($in['annual_income_lpa']) ? (float)$in['annual_income_lpa'] : null,$in['about_me'] ?? null,$in['bio'] ?? null,$in['profile_photo_url'] ?? null,$in['cover_photo_url'] ?? null]);
    cache_set("m_{$uid}", null, 1);
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'send_interest' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = require_auth($pdo); $uid = (int)$u['id']; $to = (int)(json_in()['to_user_id'] ?? 0);
    if ($to <= 0 || $to === $uid) { http_response_code(422); echo json_encode(['ok'=>false]); exit; }
    $pdo->prepare("INSERT INTO interests(from_user_id,to_user_id,status) VALUES(?,?,'pending') ON DUPLICATE KEY UPDATE status='pending'")->execute([$uid,$to]);
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'requests') {
    $u = require_auth($pdo); $uid = (int)$u['id'];
    $st = $pdo->prepare("SELECT i.id,i.status,i.created_at,u.full_name,p.city,p.profile_photo_url FROM interests i INNER JOIN users u ON u.id=i.from_user_id LEFT JOIN profiles p ON p.user_id=u.id WHERE i.to_user_id=? ORDER BY i.id DESC");
    $st->execute([$uid]); echo json_encode(['ok' => true, 'items' => $st->fetchAll()]); exit;
}

if ($api === 'respond_interest' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = require_auth($pdo); $uid = (int)$u['id']; $in = json_in();
    $id = (int)($in['interest_id'] ?? 0); $status = in_array(($in['status'] ?? ''), ['accepted','declined'], true) ? $in['status'] : 'declined';
    $pdo->prepare("UPDATE interests SET status=? WHERE id=? AND to_user_id=?")->execute([$status,$id,$uid]);
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'plans') {
    echo json_encode(['ok' => true, 'items' => $pdo->query("SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order")->fetchAll()]); exit;
}

if ($api === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = require_auth($pdo); $uid = (int)$u['id']; $planId = (int)(json_in()['plan_id'] ?? 0);
    $ps = $pdo->prepare("SELECT * FROM plans WHERE id=? AND is_active=1"); $ps->execute([$planId]); $p = $ps->fetch();
    if (!$p) { http_response_code(404); echo json_encode(['ok'=>false]); exit; }
    $pdo->prepare("UPDATE subscriptions SET status='expired' WHERE user_id=? AND status='active'")->execute([$uid]);
    $pdo->prepare("INSERT INTO subscriptions(user_id,plan_id,status,started_at,expires_at,payment_ref) VALUES(?,?,'active',NOW(),DATE_ADD(NOW(),INTERVAL ? DAY),'MANUAL-UPI')")
        ->execute([$uid,$planId,(int)$p['duration_days']]);
    echo json_encode(['ok' => true, 'message' => 'Subscription updated']); exit;
}

if ($api === 'settings') {
    $rows = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll(); $o = [];
    foreach ($rows as $r) $o[$r['setting_key']] = $r['setting_value'];
    echo json_encode(['ok' => true, 'items' => $o]); exit;
}

if ($api === 'admin_pending') {
    require_admin($pdo);
    $rows = $pdo->query("SELECT u.id,u.full_name,u.email,u.phone,u.status,p.gender,p.city,p.education,p.bio FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.role='user' AND u.status='pending' ORDER BY u.id DESC")->fetchAll();
    echo json_encode(['ok' => true, 'items' => $rows]); exit;
}

if ($api === 'admin_approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin($pdo); $uid = (int)(json_in()['user_id'] ?? 0);
    $pdo->prepare("UPDATE users SET status='approved', rejection_reason=NULL WHERE id=? AND role='user'")->execute([$uid]);
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'admin_reject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin($pdo); $in = json_in(); $uid = (int)($in['user_id'] ?? 0); $reason = trim((string)($in['reason'] ?? 'Not fit'));
    $pdo->prepare("UPDATE users SET status='rejected', rejection_reason=? WHERE id=? AND role='user'")->execute([$reason,$uid]);
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'admin_plans') { require_admin($pdo); echo json_encode(['ok' => true, 'items' => $pdo->query("SELECT * FROM plans ORDER BY sort_order,id")->fetchAll()]); exit; }

if ($api === 'admin_save_plan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin($pdo); $in = json_in(); $id = (int)($in['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE plans SET plan_name=?,price_inr=?,duration_days=?,max_contact_views=?,has_priority_listing=?,has_advanced_filters=?,is_active=?,sort_order=? WHERE id=?")
            ->execute([$in['plan_name'],(float)$in['price_inr'],(int)$in['duration_days'],(int)$in['max_contact_views'],!empty($in['has_priority_listing'])?1:0,!empty($in['has_advanced_filters'])?1:0,!empty($in['is_active'])?1:0,(int)$in['sort_order'],$id]);
    } else {
        $code = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string)$in['plan_name'])) . '-' . random_int(100, 999);
        $pdo->prepare("INSERT INTO plans(plan_code,plan_name,price_inr,duration_days,max_contact_views,has_priority_listing,has_advanced_filters,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$code,$in['plan_name'],(float)$in['price_inr'],(int)$in['duration_days'],(int)$in['max_contact_views'],!empty($in['has_priority_listing'])?1:0,!empty($in['has_advanced_filters'])?1:0,!empty($in['is_active'])?1:0,(int)$in['sort_order']]);
    }
    echo json_encode(['ok' => true]); exit;
}

if ($api === 'admin_save_setting' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin($pdo); $in = json_in(); $k = trim((string)($in['key'] ?? '')); $v = trim((string)($in['value'] ?? ''));
    if ($k === '') { http_response_code(422); echo json_encode(['ok'=>false]); exit; }
    $pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$v]);
    echo json_encode(['ok' => true]); exit;
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => 'Not found']);

