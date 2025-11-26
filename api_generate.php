<?php
// api_generate.php (v16.0 - Extracción Inteligente Universal de 15 Dígitos)
// Generador Principal (Meinungsplatz)

// 1. Limpieza de Buffer (Evita errores de JSON por espacios o warnings)
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';
require_once 'maintenance_check.php';

// Helper para salida JSON segura
function send_json($data, $code = 200) {
    ob_end_clean(); // Borra cualquier basura previa
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    // 2. Auth
    if (!isset($_SESSION['user']) || !($user = $_SESSION['user'])) {
        send_json(['success' => false, 'message' => 'No autorizado.'], 403);
    }

    // 3. Membresía
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
        send_json(['success' => false, 'message' => 'Membresía vencida.', 'error_type' => 'membership_expired'], 403);
    }

    // 4. Validación de Inputs
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') send_json(['success' => false, 'message' => 'Método inválido.'], 405);

    $urls = trim($_POST['urls'] ?? '');
    $projektnummer = trim($_POST['projektnummer'] ?? '');

    if (empty($urls)) send_json(['success' => false, 'message' => 'La URL es obligatoria.'], 400);
    if (empty($projektnummer)) send_json(['success' => false, 'message' => 'El Projektnummer es obligatorio.'], 400);

    // 5. EXTRACCIÓN INTELIGENTE DE ID (Motor Universal)
    $user_id = null;
    
    // Regex: Busca exactamente 15 dígitos numéricos consecutivos.
    // (?<!\d) asegura que no haya un dígito antes (ej. no captura parte de un número de 16)
    // (\d{15}) captura los 15 dígitos
    // (?!\d) asegura que no haya un dígito después
    if (preg_match('/(?<!\d)(\d{15})(?!\d)/', $urls, $matches)) {
         $user_id = $matches[1];
    }

    if (!$user_id) {
        // Si falla, intentamos buscar línea por línea (por si pegaron mucho texto)
        $lines = explode("\n", str_replace("\r", "", $urls));
        foreach ($lines as $line) {
            if (preg_match('/(?<!\d)(\d{15})(?!\d)/', trim($line), $matches)) {
                 $user_id = $matches[1]; break;
            }
        }
    }

    if (!$user_id) {
        send_json(['success' => false, 'message' => 'No se encontró ninguna cifra de 15 dígitos en el texto proporcionado.'], 400);
    }

    // 6. Buscar SubID en la DB
    $stmt = $pdo->prepare("
        SELECT m.subid, u.username as added_by_username, m.pais
        FROM projektnummer_subid_map m
        LEFT JOIN usuarios u ON m.added_by_user_id = u.id
        WHERE TRIM(m.projektnummer) = ?
        ORDER BY m.id DESC LIMIT 1
    ");
    $stmt->execute([$projektnummer]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $subid = (string)$result['subid'];
        
        // Construir Jumper
        $jumper = "https://survey.maximiles.com/complete?p=" . urlencode($projektnummer . '_' . $subid) . "&m=" . urlencode($user_id);
        
        // 7. Actualizar Contador
        $new_count = $jumper_count + 1;
        $stmt = $pdo->prepare("UPDATE usuarios SET jumper_count = ? WHERE id = ?");
        $stmt->execute([$new_count, $user['id']]);
        $_SESSION['user']['jumper_count'] = $new_count;
        
        // Limpiar Caché (Importante para que el dashboard se actualice)
        if (isset($_SESSION['stats_cache'])) unset($_SESSION['stats_cache']);

        if (function_exists('logActivity')) {
            logActivity($pdo, $user['id'], $user['username'], 'Generar Meinungsplatz API Exitoso', "P:{$projektnummer}");
        }

        send_json([
            'success' => true,
            'message' => "¡Jumper generado!",
            'jumper' => $jumper,
            'subid' => $subid,
            'projektnummer' => $projektnummer,
            'added_by' => $result['added_by_username'] ?? 'Sistema',
            'pais' => $result['pais'] ?? 'N/A'
        ]);

    } else {
        // SubID no encontrado
        send_json([
            'success' => false,
            'error_type' => 'subid_not_found',
            'message' => "No tenemos SubID registrado para P: {$projektnummer}",
            'projektnummer' => $projektnummer
        ]);
    }

} catch (Throwable $e) {
    error_log("Fatal Error api_generate: " . $e->getMessage());
    send_json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}
?>