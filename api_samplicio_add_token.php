<?php
// api_samplicio_add_token.php
// API segura para que usuarios PRO/Admin añadan nuevos pares Hostname/Token

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// --- 1. Auth y Permisos (¡IMPORTANTE!) ---
// Solo 'ADMINISTRADOR' y 'PRO' pueden añadir nuevos tokens.
if (!isset($_SESSION['user']) || !($user = $_SESSION['user']) || !in_array($user['membership_type'], ['ADMINISTRADOR', 'PRO'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para añadir tokens. Se requiere membresía PRO o superior.']);
    exit;
}
$userId = $user['id']; // ID del usuario que lo añade
// --- FIN AUTH ---

// --- 2. Validación Sesión Única ---
if (isset($user['id']) && isset($_SESSION['session_token'])) {
    try {
        $stmt_check = $pdo->prepare("SELECT current_session_token FROM usuarios WHERE id = ?");
        $stmt_check->execute([$user['id']]);
        $db_token = $stmt_check->fetchColumn();
        if ($db_token !== $_SESSION['session_token'] && $user['membership_type'] !== 'ADMINISTRADOR') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión inválida (iniciada en otro dispositivo).']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de DB al verificar sesión.']);
        exit;
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no encontrada.']);
    exit;
}
// --- Fin Validación ---

// 3. Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 4. Validar Inputs
$hostname = trim(strtolower($_POST['hostname'] ?? ''));
$token = trim($_POST['token'] ?? '');

// Limpiar el hostname de 'http://', 'https://', 'www.' y barras al final
$hostname = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $hostname);
$hostname = rtrim($hostname, '/');

if (empty($hostname) || empty($token)) {
    http_response_code(400);
    $errorMsg = 'El Hostname y el Token son obligatorios.';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

// 5. Insertar en la Base de Datos
try {
    // Usamos "INSERT ... ON DUPLICATE KEY UPDATE"
    // Si el hostname YA existe, simplemente actualiza el token.
    // Si no existe, lo crea.
    $stmt = $pdo->prepare(
        "INSERT INTO samplicio_tokens (hostname, token, added_by_user_id, created_at) 
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE 
         token = VALUES(token), 
         added_by_user_id = VALUES(added_by_user_id)"
    );
    
    if ($stmt->execute([$hostname, $token, $userId])) {
        logActivity($pdo, $user['id'], $user['username'], 'Samplicio: Añadir/Actualizar Token', "Host: {$hostname}");
        echo json_encode(['success' => true, 'message' => '¡Jumper (Token/Host) guardado con éxito!']);
    } else {
        throw new PDOException("La consulta de inserción falló.");
    }

} catch (PDOException $e) {
    error_log("Error en api_samplicio_add_token: " . $e.getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de Base de Datos al guardar el token.']);
}
exit;
?>