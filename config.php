<?php
// config.php (v1.4 - Corrige API Key de Cloudflare)

// --- Configuración de Base de Datos ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'surveyju_encuestas_db');
define('DB_USER', 'surveyju_encuestas_db');
define('DB_PASS', 'Freddy9no@'); // Contraseña correcta
define('DB_CHARSET', 'utf8mb4');

// --- Configuración de PDO ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
     error_log("Error de conexión a la base de datos: " . $e->getMessage());
     die("Error de conexión con la base de datos. Por favor, inténtelo más tarde."); 
}

// --- Configuración de la API de Cloudflare ---
define('CLOUDFLARE_ZONE_ID', 'c2e02937297b7844eb9a63b3ef2fd0f0'); 
// *** CORRECCIÓN: El valor de API_KEY estaba incorrecto ***
define('CLOUDFLARE_API_KEY', '7a163bf4a5e8e8713b82128369a473cb71062'); // Pega tu API Key Global aquí (NO una URL)
define('CLOUDFLARE_EMAIL', 'surveyjuniorga@gmail.com'); // Pega tu email aquí

// --- Configuración de Notificaciones de Telegram ---
// Pega el Token que te dio @BotFather
define('TELEGRAM_BOT_TOKEN', '8350960564:AAGDd0gWqldabIKU4OXITVH_-6tZGGfvwSY'); 
// Pega el ID negativo de tu grupo (incluyendo el guion -)
define('TELEGRAM_CHAT_ID', '-1003198669785');

// (Tu config.php... )

// Reemplaza la clave de Gemini o añade esta nueva:
define('PERPLEXITY_API_KEY', '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');

?>