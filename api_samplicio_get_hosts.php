<?php
// api_samplicio_get_hosts.php
// API segura para obtener la lista de hostnames para el autocompletado.

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// --- 1. Auth (Cualquier usuario logueado) ---
// Cualquiera que pueda USAR el generador, puede VER la lista de hosts.
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'hostnames' => []]);
    exit;
}
// --- FIN AUTH ---

// --- 2. Validación Sesión Única ---
if (isset($user['id']) && isset($_SESSION['session_token'])) {
    try {
        $stmt_check = $pdo->prepare("SELECT current_session_token FROM usuarios WHERE id = ?");
        $stmt_check->execute([$user['id']]);
        $db_token = $stmt_check->fetchColumn();
        if ($db_token !== $_SESSION['session_token'] && $user['membership_type'] !== 'ADMINISTRADOR') {
            http_response_code(401);
            echo json_encode(['success' => false, 'hostnames' => []]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'hostnames' => []]);
        exit;
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'hostnames' => []]);
    exit;
}
// --- Fin Validación ---

// 3. Consultar la Base de Datos
try {
    // Seleccionamos solo los hostnames, ordenados alfabéticamente
    $stmt = $pdo->prepare("SELECT hostname FROM samplicio_tokens ORDER BY hostname ASC");
    $stmt->execute();
    
    // Usamos fetchAll con PDO::FETCH_COLUMN para obtener una lista simple
    // Resultado: ["host1.com", "host2.com", "host3.com"]
    $hostnames = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); 
    
    echo json_encode(['success' => true, 'hostnames' => $hostnames]);

} catch (PDOException $e) {
    error_log("Error en api_samplicio_get_hosts: " . $e.getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'hostnames' => [], 'message' => 'Error de DB']);
}
exit;
?>