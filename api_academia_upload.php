<?php
// api_academia_upload.php
// Maneja la subida segura de archivos para la academia (Videos/PDFs)

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php'; 

// --- 1. Auth (Solo Admin) ---
if (!isset($_SESSION['user']) || !($user = $_SESSION['user']) || $user['membership_type'] !== 'ADMINISTRADOR') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

// --- 2. Validación de Sesión Única ---
if (isset($user['id']) && isset($_SESSION['session_token'])) {
    try {
        $stmt_check = $pdo->prepare("SELECT current_session_token FROM usuarios WHERE id = ?");
        $stmt_check->execute([$user['id']]);
        $db_token = $stmt_check->fetchColumn();
        if ($db_token !== $_SESSION['session_token']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión inválida (iniciada en otro dispositivo).']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de DB al verificar sesión.']);
        exit;
    }
}

// 3. Validar Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 4. Validar Archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $error_msg = 'Error en la subida. Código: ' . ($_FILES['file']['error'] ?? 'N/A');
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

$file = $_FILES['file'];
$tipo = $_POST['tipo'] ?? 'video'; // 'video' o 'pdf'

// 5. Configuración de Seguridad
$max_size = 50 * 1024 * 1024; // 50 MB
$allowed_types = [
    'video' => ['video/mp4', 'video/webm', 'video/ogg'],
    'pdf' => ['application/pdf']
];
$allowed_extensions = [
    'video' => ['mp4', 'webm', 'ogg'],
    'pdf' => ['pdf']
];

if (!isset($allowed_types[$tipo])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de contenido no válido.']);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (Máx 50MB).']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types[$tipo])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo (MIME) no permitido para esta categoría.']);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowed_extensions[$tipo])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Extensión de archivo no permitida.']);
    exit;
}

// 6. Mover Archivo
$upload_dir = __DIR__ . '/uploads/academia/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        http_response_code(500);
        error_log("Error fatal: No se pudo crear el directorio de subida en: " . $upload_dir);
        echo json_encode(['success' => false, 'message' => 'Error interno al crear el directorio de subida.']);
        exit;
    }
}

// Crear un nombre de archivo seguro y único
$safe_filename = "curso_" . time() . "_" . bin2hex(random_bytes(8)) . "." . $extension;
$destination_path = $upload_dir . $safe_filename;
$web_path = 'uploads/academia/' . $safe_filename; // Ruta para guardar en la DB

if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
    http_response_code(500);
    error_log("Error al mover el archivo subido a: " . $destination_path);
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar el archivo.']);
    exit;
}

// 7. Devolver la URL
echo json_encode(['success' => true, 'url' => $web_path]);
exit;
?>