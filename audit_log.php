<?php

function audit_log(PDO $pdo, $event_type, $details = null, $user_id = null, $user_name = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user, event_type, event_details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_name, $event_type, $details, $ip]);
}

function audit_log_current_user(PDO $pdo, $event_type, $details = null) {
    $user_id = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    $user_name = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : null;
    audit_log($pdo, $event_type, $details, $user_id, $user_name);
}
