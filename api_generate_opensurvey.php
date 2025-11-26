<?php
// api_generate_opensurvey.php (v16.0 - Full Features: Híbrido + Multisesión + LOI)
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// --- 1. AUTENTICACIÓN ---
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

// --- 2. MEMBRESÍA ---
$membership_type = $user['membership_type'] ?? 'VENCIDO';
$jumper_count = (int)($user['jumper_count'] ?? 0);
$jumper_limit = (int)($user['jumper_limit'] ?? 0);
$membership_expires = $user['membership_expires'] ? new DateTime($user['membership_expires']) : null;
$now = new DateTime();
$can_generate = false;

switch ($membership_type) {
    case 'ADMINISTRADOR': 
        $can_generate = true; 
        break;
    case 'PRO': 
        if ($membership_expires && $membership_expires > $now) $can_generate = true; 
        break;
    case 'PRUEBA GRATIS': 
        if ($jumper_count < $jumper_limit) $can_generate = true; 
        break;
}

if (!$can_generate) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Membresía vencida o límite alcanzado.', 'error_type' => 'membership_expired']);
    exit;
}

// --- 3. VALIDACIÓN DE SESIÓN (Soporte Multisesión) ---
// Usamos la función maestra de functions.php que respeta el flag 'allow_multisession'
if (!isSessionValid($pdo, $user)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión inválida (iniciada en otro dispositivo).']);
    exit;
}

// Validar Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']); 
    exit; 
}

// --- 4. LÓGICA PRINCIPAL ---
$mode = $_POST['mode'] ?? 'tunnel'; // Por defecto túnel si no se especifica

if ($mode === 'classic') {
    // ============================================================
    // MODO CLÁSICO (Vieja Seguridad - Rápido)
    // ============================================================
    $input_url = trim($_POST['urls'] ?? '');
    
    if (empty($input_url)) { 
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'URL requerida.']); 
        exit; 
    }

    $parsed = parse_url($input_url);
    if (!isset($parsed['query'])) { 
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'URL inválida o sin parámetros.']); 
        exit; 
    }
    
    parse_str($parsed['query'], $params);
    $account = $params['account'] ?? null;
    $project = $params['project'] ?? null;
    $uuid = $params['uuid'] ?? null;

    if ($account && $project && $uuid) {
        $url_final = "https://www.opensurvey.com/survey/".rawurlencode($account)."/".rawurlencode($project)."?statusBack=1&respBack=".urlencode($uuid);
        
        // Actualizar DB
        $new_count = $jumper_count + 1;
        $stmt = $pdo->prepare("UPDATE usuarios SET jumper_count = ? WHERE id = ?");
        $stmt->execute([$new_count, $user['id']]);
        $_SESSION['user']['jumper_count'] = $new_count;
        
        if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']);
        
        logActivity($pdo, $user['id'], $user['username'], 'Generar Opensurvey (Clásico)', "Project: {$project}");
        
        echo json_encode([
            'success' => true, 
            'jumper' => $url_final,
            'subid' => $project,
            'added_by' => 'Sistema',
            'pais' => 'N/A'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros (account, project, uuid).']);
    }

} else {
    // ============================================================
    // MODO TÚNEL (Nueva Seguridad - Iframe + Timer)
    // ============================================================
    $start_url = trim($_POST['start_url'] ?? '');
    
    if (empty($start_url)) { 
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'URL de inicio requerida.']); 
        exit; 
    }

    $parsed = parse_url($start_url);
    $params = [];
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $params);
    }

    $account = $params['account'] ?? null;
    $project = $params['project'] ?? null;
    $uuid = $params['uuid'] ?? null;

    if ($account && $project && $uuid) {
        // Construir Link Final (StatusBack=1)
        $final_jumper = "https://www.opensurvey.com/survey/".rawurlencode($account)."/".rawurlencode($project)."?statusBack=1&respBack=".urlencode($uuid);
        
        // --- Cálculo Inteligente de Tiempo (LOI) ---
        
        // 1. Recibir minutos del usuario
        $user_minutes = isset($_POST['custom_minutes']) && is_numeric($_POST['custom_minutes']) 
                        ? (int)$_POST['custom_minutes'] 
                        : 10;
        
        // 2. Limitar rango (1 a 120 min)
        if ($user_minutes < 1) $user_minutes = 1;
        if ($user_minutes > 120) $user_minutes = 120;

        // 3. Convertir y añadir Jitter (Factor Humano)
        $base_seconds = $user_minutes * 60;
        $jitter = rand(5, 45); // Entre 5 y 45 segundos extra aleatorios
        $final_wait_time = $base_seconds + $jitter;

        // Actualizar DB
        $new_count = $jumper_count + 1;
        $stmt = $pdo->prepare("UPDATE usuarios SET jumper_count = ? WHERE id = ?");
        $stmt->execute([$new_count, $user['id']]);
        $_SESSION['user']['jumper_count'] = $new_count;
        
        if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']);
        
        logActivity($pdo, $user['id'], $user['username'], 'Opensurvey Túnel V4', "Project: {$project} (Espera: {$user_minutes}m + {$jitter}s)");

        echo json_encode([
            'success' => true,
            'start_url' => $start_url, 
            'final_jumper' => $final_jumper,
            'wait_time' => $final_wait_time
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se detectaron los parámetros necesarios (account, project, uuid).']);
    }
}
exit;
?>