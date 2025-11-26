<?php
// logout.php (v3.0 - Fix Online Status)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';
require 'functions.php'; // Para clearRememberMeCookie

if (isset($_SESSION['user'])) {
    try {
        // *** CORRECCIÓN: Limpiar token Y marcar como OFFLINE ***
        $stmt = $pdo->prepare("UPDATE usuarios SET current_session_token = NULL, online = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        
        // Registrar salida en logs (opcional pero útil)
        if (function_exists('logActivity')) {
            logActivity($pdo, $_SESSION['user']['id'], $_SESSION['user']['username'], 'Logout Exitoso');
        }
        
    } catch (PDOException $e) {
        error_log("Error al limpiar token en logout: " . $e->getMessage());
    }
}

// Limpiar cookie de "Recordarme"
if (function_exists('clearRememberMeCookie')) {
    clearRememberMeCookie();
}

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir a index.php
header("Location: index.php");
exit;
?>