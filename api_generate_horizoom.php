<?php
// api_generate_horizoom.php (v10.2 - Fix Cache Stats)
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'No autorizado.']); exit;
}

// Validación Membresía
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

// Validación Sesión
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

$urls = trim($_POST['urls'] ?? '');
if (empty($urls)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'URL requerida.']); exit; }

$isurvey_value = null;
try {
    $query_string = parse_url($urls, PHP_URL_QUERY);
    if ($query_string) {
        parse_str($query_string, $params);
        $isurvey_value = $params['i_survey'] ?? $params['a'] ?? null;
    }
} catch (Exception $e) {}

if (empty($isurvey_value)) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Falta parámetro i_survey o a.']); exit;
}

$jumper = "https://routing.horizoom.io/?m=6006&return=complete&i_survey=" . urlencode($isurvey_value);

// --- ACTUALIZAR DB & BORRAR CACHÉ ---
$new_count = $jumper_count + 1;
$stmt = $pdo->prepare("UPDATE usuarios SET jumper_count = ? WHERE id = ?");
$stmt->execute([$new_count, $user['id']]);
$_SESSION['user']['jumper_count'] = $new_count;

if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']); // <--- EL FIX CLAVE

logActivity($pdo, $user['id'], $user['username'], 'Generar Horizoom Exitoso', "i_survey: {$isurvey_value}");

echo json_encode([
    'success' => true,
    'message' => "¡Jumper Horizoom Generado!",
    'jumper' => $jumper,
    'subid' => $isurvey_value,
    'added_by' => 'Sistema',
    'pais' => 'N/A'
]);
exit;
?>