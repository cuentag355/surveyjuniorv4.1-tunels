<?php
// api_ping.php (v3.0 - Full Features: Mantenimiento + Multisesión)
// Un "heartbeat" ligero que mantiene la sesión viva y verifica el estado del sistema.

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once 'config.php';
require_once 'functions.php';

// 🔥 1. VERIFICACIÓN DE MANTENIMIENTO 🔥
// Esto es vital para que el Dashboard sepa cuándo bloquear la pantalla.
require_once 'maintenance_check.php'; 

// --- 2. AUTENTICACIÓN BÁSICA ---
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no encontrada.']);
    exit;
}

// --- 3. VALIDACIÓN DE SESIÓN INTELIGENTE (Multisesión) ---
// Usamos la función maestra creada en functions.php.
// Si el usuario tiene 'allow_multisession = 1', esta función devolverá TRUE 
// aunque el token haya cambiado, permitiendo el uso simultáneo en varios perfiles.
if (!isSessionValid($pdo, $user)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión inválida (iniciada en otro dispositivo).']);
    exit;
}

// --- 4. EJECUTAR PING ---
// Actualiza 'last_activity' en la DB para que no te saque por inactividad.
if (function_exists('pingUserActivity')) {
    pingUserActivity($pdo, $user['id']);
    
    // Respuesta exitosa (200 OK)
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Session ping ok.']);
    exit;
}

// Fallback por si la función no existe (no debería pasar)
http_response_code(500);
echo json_encode(['success' => false, 'message' => 'Error interno del servidor (Fn no encontrada).']);
?>