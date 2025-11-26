<?php
// api_ranking.php (v3.1 - Buffer Clean Fix)
// Fix: Limpieza de buffer para evitar errores de JSON malformado

// 1. Iniciar Buffer (Captura cualquier eco o warning accidental)
ob_start();
error_reporting(0); // Ocultar errores visuales para no romper JSON
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';

// Función auxiliar para salida segura
function send_json($data) {
    ob_end_clean(); // Borrar cualquier basura anterior
    echo json_encode($data);
    exit;
}

try {
    // Lógica de sesión (Solo lectura, no estricta)
    $user_id = $_SESSION['user']['id'] ?? 0;
    if ($user_id > 0 && function_exists('updateUserActivity')) {
        updateUserActivity($pdo, $user_id);
    }

    $response = [
        'success' => true,
        'ranking' => []
    ];

    // Consulta Ranking
    // Busca usuarios con más SubIDs aportados
    $stmt = $pdo->prepare("
        SELECT 
            u.username, 
            COUNT(a.id) as subid_count
        FROM activity_log a
        JOIN usuarios u ON a.user_id = u.id
        WHERE a.action LIKE '%Añadir SubID%' OR a.action LIKE '%Mapeo Manual%'
        GROUP BY u.id, u.username
        ORDER BY subid_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $ranking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rank = 1;
    foreach ($ranking_data as $row) {
        $response['ranking'][] = [
            'rank' => $rank,
            'avatar_url' => 'https://api.dicebear.com/8.x/adventurer/svg?seed=' . urlencode($row['username']),
            'username' => $row['username'],
            'count' => (int) $row['subid_count']
        ];
        $rank++;
    }

    send_json($response);

} catch (Exception $e) {
    // En caso de error, enviar JSON de error válido
    send_json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>