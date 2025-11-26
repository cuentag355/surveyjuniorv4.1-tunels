<?php
// api_check_subid.php
// Verifica si un par P/S ya existe en la base de datos.
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php'; 

// --- 1. Auth (Cualquier usuario logueado) ---
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

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

// 3. Validar método y Inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$projektnummer = trim($_POST['projektnummer'] ?? '');
$subid = trim($_POST['subid'] ?? '');

if (empty($projektnummer) || empty($subid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Projektnummer y SubID son requeridos.']);
    exit;
}

// 4. Consultar la Base de Datos
try {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM projektnummer_subid_map WHERE projektnummer = ? AND subid = ?");
    $stmt_check->execute([$projektnummer, $subid]);
    $count = $stmt_check->fetchColumn();
    
    echo json_encode(['success' => true, 'exists' => ($count > 0)]);
    
} catch (PDOException $e) {
    error_log("Error en api_check_subid: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de Base de Datos al consultar.']);
}
exit;
?>