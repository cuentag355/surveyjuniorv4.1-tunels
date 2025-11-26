<?php
// api_mp_tunnel.php (v1.0 - Lógica Meinungsplatz Túnel)
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// 1. Auth
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) { http_response_code(403); exit(json_encode(['success'=>false, 'message'=>'No autorizado'])); }
if (!isSessionValid($pdo, $user)) { http_response_code(401); exit(json_encode(['success'=>false, 'message'=>'Sesión inválida'])); }

// 2. Validación Inicial (Paso 1: Verificar si tenemos SubID)
if (isset($_POST['action']) && $_POST['action'] === 'check_subid') {
    $projekt = trim($_POST['projektnummer'] ?? '');
    
    // Buscar en la DB
    $mapData = findSubidForProjektnummer($pdo, $projekt); // Función existente en functions.php
    
    if ($mapData) {
        // ¡Éxito! Tenemos el SubID
        echo json_encode([
            'success' => true,
            'subid' => $mapData['subid'], // Se lo enviamos al JS para que lo guarde temporalmente
            'message' => 'SubID encontrado: ' . $mapData['subid']
        ]);
    } else {
        // Fallo: No tenemos SubID
        echo json_encode([
            'success' => false,
            'error_type' => 'subid_not_found',
            'projektnummer' => $projekt,
            'message' => 'No tenemos el SubID para el proyecto ' . $projekt
        ]);
    }
    exit;
}

// 3. Generación Final (Paso 2: Construir Jumper con UserID capturado)
if (isset($_POST['action']) && $_POST['action'] === 'generate_jumper') {
    
    $projekt = $_POST['projektnummer'] ?? '';
    $subid = $_POST['subid'] ?? '';
    $userId = $_POST['user_id'] ?? ''; // Este viene de la extensión
    $mins = (int)($_POST['custom_minutes'] ?? 10);

    if ($projekt && $subid && $userId) {
        
        // Construcción del Link Final (Formato EFS/Maximiles)
        // Formato estándar: complete?p=PROJEKT_SUBID&m=USERID
        $final_jumper = "https://survey.maximiles.com/complete?p=" . urlencode($projekt . '_' . $subid) . "&m=" . urlencode($userId);
        
        // Tiempo + Jitter
        if ($mins < 1) $mins = 1; if ($mins > 120) $mins = 120;
        $jitter = rand(5, 45);
        $wait_time = ($mins * 60) + $jitter;

        // Logs y Contadores
        $new_count = ((int)$user['jumper_count']) + 1;
        $pdo->prepare("UPDATE usuarios SET jumper_count=? WHERE id=?")->execute([$new_count, $user['id']]);
        $_SESSION['user']['jumper_count'] = $new_count;
        if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']);
        logActivity($pdo, $user['id'], $user['username'], 'MP Túnel Iniciado', "P:$projekt S:$subid");

        echo json_encode([
            'success' => true,
            'final_jumper' => $final_jumper,
            'wait_time' => $wait_time
        ]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Datos incompletos para generar jumper.']);
    }
    exit;
}
?>