<?php
// api_public_activity.php (v2.0 - API Pública de Actividad REAL)
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php'; // Solo para la conexión a la DB

// Esta API es pública y no revela información sensible.

$activities = [];
try {
    // Unir usuarios para obtener el nombre
    $stmt = $pdo->prepare(
        "SELECT u.username, a.action, a.details 
         FROM activity_log a
         LEFT JOIN usuarios u ON a.user_id = u.id
         WHERE a.action IN (
            'Añadir SubID Exitoso', 
            'Admin: Añadir Mapeo Manual', 
            'Generar Meinungsplatz API Exitoso',
            'Generar Opensurvey API Exitoso', 
            'Generar OpinionEx API Exitoso'
         )
         AND a.user_id IS NOT NULL
         ORDER BY a.id DESC 
         LIMIT 20" // Obtenemos los últimos 20 para tener variedad
    );
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_activities = [];
    foreach ($logs as $log) {
        $username = $log['username'] ?? 'Alguien';
        // Acortar el nombre si es largo para que quepa en el toast
        if (strlen($username) > 10) {
            $username = substr($username, 0, 8) . '...';
        }
        
        $message = '';
        $action = $log['action'];
        $details = $log['details'];

        if (strpos($action, 'Generar') !== false) {
            // Lógica para generación de Jumper
            if (strpos($action, 'Meinungsplatz') !== false) {
                // Meinungsplatz usa "P:12345, S:subid" en details
                $projekt = explode(',', $details)[0] ?? 'un jumper'; 
                $projekt_num = str_replace(['P:', 'p:'], '', $projekt);
                $message = "generó un jumper para <b>" . htmlspecialchars(trim($projekt_num)) . "</b>";
            } else if (strpos($action, 'Opensurvey') !== false) {
                 // Opensurvey registra "Project: 12345"
                 if (preg_match('/Project: ([a-zA-Z0-9]+)/', $details, $matches)) {
                     $message = "generó un jumper Opensurvey para <b>" . htmlspecialchars($matches[1]) . "</b>";
                 } else {
                     $message = "generó un jumper Opensurvey";
                 }
            } else if (strpos($action, 'OpinionEx') !== false) {
                 // OpinionEx no tiene un identificador fácil, solo el evento
                 $message = "generó un jumper OpinionEx";
            } else {
                 $message = "generó un jumper";
            }
        } else if (strpos($action, 'SubID') !== false) {
            // Lógica para añadir SubID
            $projekt = explode(',', $details)[0] ?? 'un SubID'; 
            $projekt_num = str_replace(['P:', 'p:'], '', $projekt);
            $message = "añadió un SubID para <b>" . htmlspecialchars(trim($projekt_num)) . "</b>";
        }
        
        $formatted_activities[] = [
            'user' => htmlspecialchars($username),
            'message' => $message 
        ];
    }
    
    // Mezclar para que no sea siempre el mismo orden en los toasts
    shuffle($formatted_activities);
    
    echo json_encode(['success' => true, 'activities' => $formatted_activities]);

} catch (PDOException $e) {
    error_log("Error en api_public_activity: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de DB']);
}
exit;
?>