<?php
// api_submit_proof.php (v7.1 - Fix JSON Error & Buffer Clean)
// Se añade limpieza de buffer de salida para evitar errores de sintaxis JSON.

// Iniciar buffer de salida: Captura cualquier eco/error accidental
ob_start();

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Incluir archivos (cualquier espacio en blanco aquí será capturado por ob_start)
require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// Función auxiliar para enviar JSON limpio y salir
function send_json_response($data, $code = 200) {
    // Borrar cualquier salida previa (espacios, warnings, etc.)
    ob_clean(); 
    
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit; // Detener script inmediatamente
}

// 1. Auth
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    send_json_response(['success' => false, 'message' => 'No autorizado.'], 403);
}
$userId = $user['id'];

// 2. Validar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

// 3. Inputs
$reference = trim($_POST['reference_number'] ?? '');
$amount = trim($_POST['amount'] ?? '');
$method = trim($_POST['payment_method'] ?? 'Desconocido');
$notes = trim($_POST['notes'] ?? '');

if (empty($reference) || empty($amount)) {
    send_json_response(['success' => false, 'message' => 'Faltan datos obligatorios (Referencia o Monto).'], 400);
}

// 4. Archivo
if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['proof_image']['error'] ?? 'No file';
    send_json_response(['success' => false, 'message' => "Error al recibir el archivo. Código: $err"], 400);
}

$file = $_FILES['proof_image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
    send_json_response(['success' => false, 'message' => 'Solo se permiten imágenes (JPG, PNG, GIF).'], 400);
}

// 5. Guardar Archivo
$upload_dir = __DIR__ . '/uploads/proofs/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        send_json_response(['success' => false, 'message' => 'Error del servidor: No se pudo crear carpeta de subidas.'], 500);
    }
}

$filename = "proof_{$userId}_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
$dest_path = $upload_dir . $filename;
$web_path = "uploads/proofs/$filename";

if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    send_json_response(['success' => false, 'message' => 'Error al guardar el archivo en el disco.'], 500);
}

// 6. Guardar en DB
try {
    $admin_notes = "Monto: $amount ($method)" . ($notes ? " - Nota: $notes" : "");
    
    $stmt = $pdo->prepare("INSERT INTO payment_proofs (user_id, username, reference_number, file_path, status, created_at, admin_notes) VALUES (?, ?, ?, ?, 'PENDIENTE', NOW(), ?)");
    $stmt->execute([$userId, $user['username'], $reference, $web_path, $admin_notes]);

    if (function_exists('logActivity')) {
        logActivity($pdo, $userId, $user['username'], 'Comprobante Subido', "Ref: $reference");
    }
    
    send_json_response(['success' => true, 'message' => 'Comprobante enviado correctamente.']);

} catch (PDOException $e) {
    @unlink($dest_path); // Borrar archivo si falla DB
    error_log("DB Error api_submit_proof: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Error de base de datos al guardar el registro.'], 500);
}
?>