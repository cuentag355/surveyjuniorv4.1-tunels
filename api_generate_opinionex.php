<?php
// api_generate_opinionex.php (v10.2 - Fix Cache Stats)
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'No autorizado.']); exit;
}

$membership_type = $user['membership_type'] ?? 'VENCIDO';
$jumper_count = (int)($user['jumper_count'] ?? 0);
$jumper_limit = (int)($user['jumper_limit'] ?? 0);
$membership_expires = $user['membership_expires'] ? new DateTime($user['membership_expires']) : null;
$now = new DateTime();
$can_generate = false;

switch ($membership_type) {
    case 'ADMINISTRADOR': $can_generate = true; break;
    case 'PRO': if ($membership_expires && $membership_expires > $now) $can_generate = true; break;
    case 'PRUEBA GRATIS': if ($jumper_count < $jumper_limit) $can_generate = true; break;
}

if (!$can_generate) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Membresía vencida.', 'error_type' => 'membership_expired']); exit;
}

if (isset($user['id']) && isset($_SESSION['session_token'])) {
    try {
        $stmt = $pdo->prepare("SELECT current_session_token FROM usuarios WHERE id = ?");
        $stmt->execute([$user['id']]);
        if ($stmt->fetchColumn() !== $_SESSION['session_token'] && $user['membership_type'] !== 'ADMINISTRADOR') {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'Sesión inválida.']); exit;
        }
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input_url = trim($_POST['input_url_opinion'] ?? '');
if (empty($input_url)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'URL requerida.']); exit; }

$parts = parse_url($input_url);
if (!isset($parts['query'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'URL sin parámetros.']); exit; }

parse_str($parts['query'], $params);
$userUD = $params['UserID'] ?? null;

if ($userUD && preg_match('/^[a-zA-Z0-9_-]+$/', $userUD)) {
    $url_final = "https://opex.panelmembers.io/p/exit?s=c&session=" . urlencode($userUD);
    
    // --- ACTUALIZAR DB & BORRAR CACHÉ ---
    $new_count = $jumper_count + 1;
    $stmt = $pdo->prepare("UPDATE usuarios SET jumper_count = ? WHERE id = ?");
    $stmt->execute([$new_count, $user['id']]);
    $_SESSION['user']['jumper_count'] = $new_count;
    
    if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']); // <--- EL FIX CLAVE
    
    logActivity($pdo, $user['id'], $user['username'], 'Generar OpinionEx API Exitoso');
    
    echo json_encode([
        'success' => true, 
        'jumper' => $url_final,
        'subid' => $userUD,
        'added_by' => 'N/A',
        'pais' => 'N/A'
    ]);
} else {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'UserID inválido o faltante.']);
}
exit;
?>