<?php
// api_admin_stats.php (v25.0 - Robust & Optimized)
// Devuelve estadísticas para el dashboard de administrador.

// 1. Iniciar Buffer y manejo de errores silencioso
ob_start(); 
error_reporting(0); // Desactivar salida de errores HTML para no romper el JSON
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';
require_once 'functions.php';

// Función helper para salir con JSON seguro
function send_json($data) {
    ob_end_clean(); // Borrar cualquier eco/warning previo
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

try {
    // 2. Auth: Verificar Admin
    if (!isset($_SESSION['user']) || !($user = $_SESSION['user']) || $user['membership_type'] !== 'ADMINISTRADOR') {
        send_json(['success' => false, 'message' => 'Acceso denegado.']);
    }

    // 3. Inicializar Estructura de Respuesta
    $response = [
        'success' => true,
        'stats' => [
            'totalUsers' => 0,
            'onlineUsers' => 0,
            'maintenance_mode' => file_exists('MAINTENANCE'),
            'academy_is_disabled' => file_exists('ACADEMIA_DISABLED')
        ],
        'chart_data' => [
            'labels' => [],
            'jumpers' => [],
            'logins' => []
        ]
    ];

    // 4. Obtener Contadores Rápidos
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $response['stats']['totalUsers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE online = 1");
    $response['stats']['onlineUsers'] = (int)$stmt->fetchColumn();

    // 5. Generar Fechas de los últimos 7 días
    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[$date] = [
            'label' => date('M d', strtotime($date)),
            'jumpers' => 0,
            'logins' => 0
        ];
    }

    // 6. Consulta Optimizada: Jumpers (Agrupado por fecha)
    // Busca acciones que contengan "Generar" y "Exitoso"
    $stmt_jumpers = $pdo->query("
        SELECT DATE(timestamp) as fecha, COUNT(*) as total 
        FROM activity_log 
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND action LIKE 'Generar % Exitoso'
        GROUP BY DATE(timestamp)
    ");
    while ($row = $stmt_jumpers->fetch(PDO::FETCH_ASSOC)) {
        if (isset($dates[$row['fecha']])) {
            $dates[$row['fecha']]['jumpers'] = (int)$row['total'];
        }
    }

    // 7. Consulta Optimizada: Logins (Agrupado por fecha)
    $stmt_logins = $pdo->query("
        SELECT DATE(timestamp) as fecha, COUNT(*) as total 
        FROM activity_log 
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND action LIKE 'Login%'
        GROUP BY DATE(timestamp)
    ");
    while ($row = $stmt_logins->fetch(PDO::FETCH_ASSOC)) {
        if (isset($dates[$row['fecha']])) {
            $dates[$row['fecha']]['logins'] = (int)$row['total'];
        }
    }

    // 8. Formatear datos para Chart.js
    foreach ($dates as $dayData) {
        $response['chart_data']['labels'][] = $dayData['label'];
        $response['chart_data']['jumpers'][] = $dayData['jumpers'];
        $response['chart_data']['logins'][] = $dayData['logins'];
    }

    // 9. Enviar Respuesta Final
    send_json($response);

} catch (PDOException $e) {
    // Error de Base de Datos
    send_json(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Error General
    send_json(['success' => false, 'message' => 'Error Servidor: ' . $e->getMessage()]);
}
?>