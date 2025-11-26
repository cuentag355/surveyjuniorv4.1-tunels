<?php
// api_chat_assistant.php (Versión Perplexity Corregida)
// Backend seguro para conectarse a la API de Perplexity.

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// --- 1. Auth (Sin cambios) ---
if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}
$userId = $user['id'];

// --- 2. Validación Sesión Única (Sin cambios) ---
if (isset($user['id']) && isset($_SESSION['session_token'])) {
    try {
        $stmt_check = $pdo->prepare("SELECT current_session_token FROM usuarios WHERE id = ?");
        $stmt_check->execute([$userId]);
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
}
// --- FIN VALIDACIÓN ---

// --- ¡MODIFICACIÓN CLAVE! ---
// 3. VALIDACIÓN DE MEMBRESÍA (Actualizada)
$membership_type = $user['membership_type'] ?? 'VENCIDO';
$can_use_chatbot = false;
$error_message = 'El Asistente de IA es una función exclusiva para miembros PRO. Por favor, actualiza tu plan.';

switch ($membership_type) {
    case 'ADMINISTRADOR':
    case 'PRO':
        $can_use_chatbot = true;
        break;
    
    // Se elimina el caso 'PRUEBA GRATIS'.
    // Ahora 'PRUEBA GRATIS' y 'VENCIDO' caen en 'default'.
    
    case 'PRUEBA GRATIS':
    case 'VENCIDO':
    default:
        $can_use_chatbot = false;
        break;
}

if (!$can_use_chatbot) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => $error_message, 'error_type' => 'membership_expired']);
    exit;
}
// --- FIN MODIFICACIÓN ---

// Actualizar actividad del usuario
if (function_exists('updateUserActivity')) {
    updateUserActivity($pdo, $userId);
}

// 4. Obtener el mensaje del usuario (Sin cambios)
$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
    exit;
}

// 5. Verificar la Clave API de Perplexity (Sin cambios)
if (!defined('PERPLEXITY_API_KEY') || PERPLEXITY_API_KEY === 'AQUI_VA_TU_NUEVA_CLAVE_DE_PERPLEXITY' || PERPLEXITY_API_KEY === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: La clave API de Perplexity no está configurada.']);
    exit;
}
$apiKey = PERPLEXITY_API_KEY;
$apiUrl = "https://api.perplexity.ai/chat/completions";

// 6. Preparar la solicitud para Perplexity (cURL) (Sin cambios)
$payload = json_encode([
    'model' => 'sonar-reasoning-pro', // Usando el último modelo que funcionó
    'messages' => [
        ['role' => 'system', 'content' => 'Eres un asistente útil.'],
        ['role' => 'user', 'content' => $user_message]
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 7. Procesar la respuesta de Perplexity (Sin cambios)
if ($http_code === 200) {
    $data = json_decode($response, true);
    
    $bot_response_text = $data['choices'][0]['message']['content'] ?? null;

    if ($bot_response_text) {
        // ¡Éxito!
        logActivity($pdo, $userId, $user['username'], 'Chatbot Exitoso (Perplexity)', substr($user_message, 0, 100) . '...');
        echo json_encode(['success' => true, 'message' => $bot_response_text]);
    } else {
        // La IA bloqueó la respuesta
        $finishReason = $data['choices'][0]['finish_reason'] ?? 'UNKNOWN';
        logActivity($pdo, $userId, $user['username'], 'Chatbot Fallido (IA Perplexity)', 'Razón: ' . $finishReason);
        echo json_encode(['success' => false, 'message' => 'La IA no pudo procesar esta solicitud (Razón: ' . $finishReason . ').']);
    }
} else {
    // Error de API (clave incorrecta, límite excedido, etc.)
    $error_details = json_decode($response, true);
    $error_message = $error_details['error']['message'] ?? 'Error desconocido';
    
    logActivity($pdo, $userId, $user['username'], 'Chatbot Fallido (API Perplexity)', 'Código: ' . $http_code . ' Msg: ' . $error_message);
    echo json_encode(['success' => false, 'message' => 'Error al contactar al servicio de IA. (Código: ' . $http_code . ' - ' . $error_message . ')']);
}
exit;
?>