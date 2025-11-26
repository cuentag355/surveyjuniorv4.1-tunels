<?php
// functions.php (v36.0 - Quantum Full: Restaurado + Multisesión + 20min)

// --- Helper de URLs ---
function secure_url($url) {
    if (strpos($url, 'https://') === 0) return $url;
    if (strpos($url, 'http://') === 0) return 'https://' . substr($url, 7);
    if (strpos($url, '/') === 0) return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    return $url;
}

// --- VALIDACIÓN DE SESIÓN MAESTRA (NUEVO) ---
// Permite multisesión si 'allow_multisession = 1'
function isSessionValid(PDO $pdo, array $user): bool {
    if (($user['membership_type'] ?? '') === 'ADMINISTRADOR') return true;
    if (!isset($_SESSION['session_token'])) return false;

    try {
        $stmt = $pdo->prepare("SELECT current_session_token, allow_multisession FROM usuarios WHERE id = ?");
        $stmt->execute([$user['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return false;

        // Si tiene Multisesión (1), pase libre.
        if (isset($data['allow_multisession']) && $data['allow_multisession'] == 1) return true;

        // Si no, validación estricta.
        if ($data['current_session_token'] !== $_SESSION['session_token']) return false;

        return true;
    } catch (PDOException $e) {
        error_log("Error isSessionValid: " . $e->getMessage());
        return true; // Fallback seguro
    }
}

// --- Actualizar Actividad (Heartbeat & Cleanup) ---
function updateUserActivity(PDO $pdo, $userId) {
    $now = time();
    $threshold = $now - 1200; // 20 Minutos (Sincronizado con Túnel)
    
    try {
        $stmt_update = $pdo->prepare("UPDATE usuarios SET last_activity = :now, online = 1, last_login = CASE WHEN last_login IS NULL THEN NOW() ELSE last_login END WHERE id = :id");
        $stmt_update->execute(['now' => $now, 'id' => $userId]);
        
        // Limpieza ocasional (5%)
        if (rand(1, 100) <= 5) { 
            $stmt_cleanup = $pdo->prepare("UPDATE usuarios SET online = 0, current_session_token = NULL WHERE last_activity < :threshold AND online = 1");
            $stmt_cleanup->execute(['threshold' => $threshold]);
        }
    } catch (PDOException $e) { error_log("Error updateUserActivity: " . $e->getMessage()); }
}

// --- Ping Ligero ---
function pingUserActivity(PDO $pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET last_activity = ? WHERE id = ?");
        $stmt->execute([time(), $userId]);
    } catch (PDOException $e) {}
}

// --- Geolocalización IP (Restaurado) ---
function updateUserLocation(PDO $pdo, int $userId): void {
    if (!isset($pdo)) return;
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip && strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
    
    if (!$ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) return;

    $location_details = 'Desconocida';
    // API Externa (Nota: Puede ser lenta, usar con precaución)
    $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,city";
    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $response = @file_get_contents($url, false, $ctx);

    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] === 'success') {
            $city = trim($data['city'] ?? ''); 
            $country = trim($data['country'] ?? '');
            if ($city && $country) $location_details = "$city, $country";
            elseif ($country) $location_details = $country;
        }
    }
    
    $location_details = mb_substr($location_details, 0, 250);
    try {
        $stmt_check = $pdo->prepare("SELECT last_location_details FROM usuarios WHERE id = ?"); 
        $stmt_check->execute([$userId]);
        if ($stmt_check->fetchColumn() !== $location_details) {
            $stmt_update = $pdo->prepare("UPDATE usuarios SET last_location_details = ? WHERE id = ?");
            $stmt_update->execute([$location_details, $userId]);
            if (isset($_SESSION['user']) && $_SESSION['user']['id'] === $userId) { $_SESSION['user']['last_location_details'] = $location_details; }
        }
    } catch (Exception $e) {}
}

// --- Logs ---
function logActivity(PDO $pdo, ?int $userId, ?string $username, string $action, ?string $details = null): void {
    try {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);
        $ip = mb_substr($ip, 0, 45);
        
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $username, mb_substr($action, 0, 255), $details ? mb_substr($details, 0, 1000) : null, $ip]);
    } catch (Exception $e) {}
}

// --- Seguridad Login ---
define('LOGIN_ATTEMPT_LIMIT', 5); 
define('LOGIN_ATTEMPT_WINDOW', 15 * 60);

function isLoginBlocked(PDO $pdo, string $ip): bool {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
        $stmt->execute([$ip, time() - LOGIN_ATTEMPT_WINDOW]);
        return ($stmt->fetchColumn() >= LOGIN_ATTEMPT_LIMIT);
    } catch (Exception $e) { return false; }
}

function recordFailedLogin(PDO $pdo, string $ip): void {
    try {
        $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, ?)")->execute([$ip, time()]);
    } catch (Exception $e) {}
}

function clearLoginAttempts(PDO $pdo, string $ip): void {
    try { $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]); } catch (Exception $e) {}
}

// --- Recordarme ---
define('REMEMBER_ME_COOKIE_NAME', 'survey_remember'); 
define('REMEMBER_ME_DURATION', 60 * 60 * 24 * 30);

function clearRememberMeCookie(): void {
    setcookie(REMEMBER_ME_COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}

function rememberUser(PDO $pdo, int $userId): void {
    try {
        $selector = bin2hex(random_bytes(16)); 
        $validator = bin2hex(random_bytes(32));
        $hash = password_hash($validator, PASSWORD_DEFAULT); 
        $exp = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
        
        $pdo->prepare("DELETE FROM persistent_logins WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("INSERT INTO persistent_logins (user_id, selector, token_hash, expires) VALUES (?, ?, ?, ?)")->execute([$userId, $selector, $hash, $exp]);
        
        setcookie(REMEMBER_ME_COOKIE_NAME, "$selector:$validator", ['expires' => time() + REMEMBER_ME_DURATION, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    } catch (Exception $e) {}
}

function validateRememberMe(PDO $pdo): ?array {
    if (empty($_COOKIE[REMEMBER_ME_COOKIE_NAME])) return null;
    $parts = explode(':', $_COOKIE[REMEMBER_ME_COOKIE_NAME]);
    if (count($parts) !== 2) { clearRememberMeCookie(); return null; }
    list($sel, $val) = $parts;

    try {
        $stmt = $pdo->prepare("SELECT * FROM persistent_logins WHERE selector = ? AND expires >= NOW()");
        $stmt->execute([$sel]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token && password_verify($val, $token['token_hash'])) {
            $stmt_u = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND active = 1 AND banned = 0");
            $stmt_u->execute([$token['user_id']]);
            $user = $stmt_u->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Evitar loop si ya hay sesión reciente (y no es multisesión)
                if ($user['current_session_token'] && $user['last_activity'] > (time() - 300) && ($user['allow_multisession']??0) == 0) {
                    return null; 
                }

                session_regenerate_id(true);
                $new_token = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE usuarios SET current_session_token = ? WHERE id = ?")->execute([$new_token, $user['id']]);
                
                $_SESSION['session_token'] = $new_token;
                $_SESSION['user'] = $user;
                
                rememberUser($pdo, $user['id']);
                updateUserActivity($pdo, $user['id']);
                
                return $user;
            }
        }
    } catch (Exception $e) {}
    clearRememberMeCookie();
    return null;
}

// --- Meinungsplatz & SubIDs ---
function findSubidForProjektnummer(PDO $pdo, string $projektnummer): ?array {
    if (!ctype_digit($projektnummer)) return null;
    try {
        $stmt = $pdo->prepare("SELECT m.subid, u.username as added_by_username, m.pais FROM projektnummer_subid_map m LEFT JOIN usuarios u ON m.added_by_user_id = u.id WHERE TRIM(m.projektnummer) = ? ORDER BY m.id DESC LIMIT 1");
        $stmt->execute([$projektnummer]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? ['subid' => $res['subid'], 'added_by' => $res['added_by_username'] ?? 'Sistema', 'pais' => $res['pais'] ?? 'Alemania'] : null;
    } catch (Exception $e) { return null; }
}

function addProjektnummerSubidMap(PDO $pdo, string $projektnummer, string $subid, int $userId, string $pais): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO projektnummer_subid_map (projektnummer, subid, pais, created_at, added_by_user_id) VALUES (?, ?, ?, NOW(), ?)");
        if ($stmt->execute([$projektnummer, $subid, $pais, $userId])) {
            $uName = 'ID:'.$userId;
            try { $u = $pdo->prepare("SELECT username FROM usuarios WHERE id=?"); $u->execute([$userId]); $name = $u->fetchColumn(); if($name) $uName=$name; } catch(Exception $e){}
            sendTelegramNotification("<b>Nuevo SubID (MP)</b>\n👤 <b>User:</b> $uName\n🌍 <b>País:</b> $pais\n🔗 <b>P:</b> $projektnummer\n🔑 <b>S:</b> $subid");
            return true;
        }
    } catch (Exception $e) {}
    return false;
}

// --- Ratings (Restaurado) ---
function getSubidRatings(PDO $pdo, string $subid): array {
    try {
        $stmt = $pdo->prepare("SELECT SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as pos, SUM(CASE WHEN rating=-1 THEN 1 ELSE 0 END) as neg FROM subid_ratings WHERE subid = ?");
        $stmt->execute([$subid]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['positive' => (int)($r['pos']??0), 'negative' => (int)($r['neg']??0)];
    } catch (Exception $e) { return ['positive'=>0,'negative'=>0]; }
}

function submitSubidRating(PDO $pdo, string $subid, int $userId, int $rating, ?string $comment): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO subid_ratings (subid, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=NOW()");
        return $stmt->execute([$subid, $userId, $rating, $comment]);
    } catch (Exception $e) { return false; }
}

function getSubidComments(PDO $pdo, string $subid, int $limit = 5): array {
    try {
        $stmt = $pdo->prepare("SELECT r.comment, r.created_at, u.username FROM subid_ratings r LEFT JOIN usuarios u ON r.user_id=u.id WHERE r.subid=? AND r.comment IS NOT NULL AND r.comment!='' ORDER BY r.created_at DESC LIMIT ?");
        $stmt->bindValue(1, $subid); $stmt->bindValue(2, $limit, PDO::PARAM_INT); $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

// --- Función Auxiliar Faltante (Restaurada) ---
function getUserRatingForSubid(PDO $pdo, string $subid, int $userId): ?int {
    try {
        $stmt = $pdo->prepare("SELECT rating FROM subid_ratings WHERE subid = ? AND user_id = ?");
        $stmt->execute([$subid, $userId]);
        $res = $stmt->fetchColumn();
        return $res !== false ? (int)$res : null;
    } catch (Exception $e) { return null; }
}

// --- Telegram ---
function sendTelegramNotification(string $message): void {
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_CHAT_ID')) return;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => TELEGRAM_CHAT_ID, 'text' => $message, 'parse_mode' => 'HTML'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

define('FUNCTIONS_PHP_LOADED', true);
?>