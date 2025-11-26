<?php
// admin.php (v37.0 - Final Stable: Ghost Users Fix + All Quantum Features)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';
require 'functions.php';

// --- 1. AUTH ---
if (!isset($_SESSION['user'])) {
    if (function_exists('validateRememberMe')) { 
        $userFromCookie = validateRememberMe($pdo); 
        if (!$userFromCookie) { header('Location: login.php'); exit; }
    } else { header('Location: login.php'); exit; }
}
if (!isset($_SESSION['user']) || $_SESSION['user']['membership_type'] !== 'ADMINISTRADOR') {
     if (isset($_COOKIE[REMEMBER_ME_COOKIE_NAME]) && function_exists('clearRememberMeCookie')) clearRememberMeCookie();
     session_unset(); session_destroy(); header('Location: login.php?error=Acceso+denegado.'); exit;
}
$user = $_SESSION['user'];

// --- 2. LIMPIEZA AUTOMÁTICA DE "USUARIOS FANTASMA" (NUEVO) ---
// Se ejecuta cada vez que el admin carga la página.
try {
    $timeout = 1200; // 20 minutos de inactividad
    $cutoff = time() - $timeout;
    // Forzar offline a quienes no tengan actividad reciente
    $pdo->query("UPDATE usuarios SET online = 0 WHERE last_activity < $cutoff AND online = 1");
} catch (Exception $e) {
    error_log("Error limpiando usuarios fantasmas: " . $e->getMessage());
}

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$message = ''; $message_type = 'info';
$section = $_GET['section'] ?? 'dashboard';

// --- 3. HELPER FUNCTIONS ---
function paginationLinks($p, $t, $b) {
    if ($t <= 1) return '';
    $h = '<div class="flex justify-center gap-2 mt-6">';
    if ($p > 1) $h .= "<a href='{$b}&page=".($p-1)."' class='px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-white border border-white/10 transition-colors'>&laquo;</a>";
    for ($i=1; $i<=$t; $i++) {
        $act = ($i==$p) ? 'bg-sj-green text-sj-dark font-bold border-sj-green' : 'bg-white/5 text-gray-400 hover:text-white border-white/10';
        if ($i==1 || $i==$t || ($i>=$p-2 && $i<=$p+2)) $h .= "<a href='{$b}&page={$i}' class='px-3 py-1 rounded-lg border {$act} transition-colors'>{$i}</a>";
        elseif ($i==$p-3 || $i==$p+3) $h .= "<span class='text-gray-600'>...</span>";
    }
    if ($p < $t) $h .= "<a href='{$b}&page=".($p+1)."' class='px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-white border border-white/10 transition-colors'>&raquo;</a>";
    $h .= '</div>'; return $h;
}

// ============================================================================
// LÓGICA DE ACCIONES (POST)
// ============================================================================

// 1. USUARIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user']) && $section == 'users') {
    $new_username = trim($_POST['new_username'] ?? ''); 
    $new_password = $_POST['new_password'] ?? ''; 
    $new_membership = $_POST['membership_type'] ?? 'PRUEBA GRATIS';
    if (empty($new_username) || empty($new_password)) { $message = "Datos incompletos."; $message_type = 'danger'; }
    else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?"); $stmt->execute([$new_username]);
            if ($stmt->fetch()) { $message = "El usuario ya existe."; $message_type = 'warning'; }
            else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $j_limit = ($new_membership === 'PRO' || $new_membership === 'ADMINISTRADOR') ? 999999 : 5;
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, active, banned, membership_type, jumper_limit, jumper_count, created_at) VALUES (?, ?, 1, 0, ?, ?, 0, NOW())");
                if ($stmt->execute([$new_username, $hash, $new_membership, $j_limit])) { 
                    $message = "Usuario creado."; $message_type = 'success'; 
                    logActivity($pdo, $user['id'], $user['username'], 'Admin: Crear Usuario', $new_username); 
                } else { $message = "Error al crear usuario."; $message_type = 'danger'; }
            }
        } catch (Exception $e) { $message = "Error DB: " . $e->getMessage(); $message_type = 'danger'; }
    }
}

// admin.php - Lógica de Edit User Actualizada

if (isset($_POST['edit_user']) && $section == 'users') {
    $uid = intval($_POST['user_id'] ?? 0); 
    $act = isset($_POST['active']) ? 1 : 0; 
    $mem = $_POST['membership_type'] ?? 'PRUEBA GRATIS'; 
    
    // Fecha manual o NULL
    $exp_input = $_POST['membership_expires'] ?? '';
    $exp = !empty($exp_input) ? $exp_input : null; 
    
    $jc = intval($_POST['jumper_count'] ?? 0); 
    $jl = intval($_POST['jumper_limit'] ?? 5); 
    $ban = isset($_POST['academia_privilege']) ? 1 : 0;
    
    // 🔥 NUEVO CAMPO: MULTISESIÓN 🔥
    $multi = isset($_POST['allow_multisession']) ? 1 : 0;
    
    try {
        if($mem === 'ADMINISTRADOR') { $jl = 999999; $exp = null; } 
        elseif($mem === 'PRO') {
            $jl = 999999; 
            if (empty($exp)) { $exp = date('Y-m-d H:i:s', strtotime('+30 days')); }
        }

        // Actualizamos la consulta para incluir allow_multisession
        $stmt = $pdo->prepare("UPDATE usuarios SET active=?, membership_type=?, membership_expires=?, jumper_count=?, jumper_limit=?, banned=?, allow_multisession=? WHERE id=?");
        if($stmt->execute([$act, $mem, $exp, $jc, $jl, $ban, $multi, $uid])) { 
            $message = "Usuario actualizado con éxito."; $message_type = 'success'; 
            logActivity($pdo, $user['id'], $user['username'], 'Admin: Editar Usuario', "ID: $uid, Multi: $multi"); 
        } else {
            $message = "No se realizaron cambios."; $message_type = 'warning';
        }
    } catch(Exception $e) { $message = "Error: ".$e->getMessage(); $message_type = 'danger'; }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['user_id'])) {
    $uid = intval($_GET['user_id']);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM persistent_logins WHERE user_id=?")->execute([$uid]);
        $pdo->prepare("DELETE FROM payment_proofs WHERE user_id=?")->execute([$uid]);
        $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$uid]);
        $pdo->commit(); $message = "Usuario eliminado."; $message_type = 'success';
    } catch(Exception $e) { $pdo->rollBack(); $message = "Error: ".$e->getMessage(); $message_type = 'danger'; }
}
if (isset($_POST['change_password'])) {
    $uid = intval($_POST['user_id'] ?? 0); $pw = $_POST['new_password'] ?? '';
    if(strlen($pw)>=6) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?")->execute([$hash, $uid]);
        $pdo->prepare("DELETE FROM persistent_logins WHERE user_id=?")->execute([$uid]);
        $message = "Contraseña actualizada."; $message_type = 'success';
    } else { $message = "Contraseña muy corta."; $message_type = 'danger'; }
}

// 2. PAGOS
if (isset($_GET['action']) && $_GET['action'] == 'approve_payment' && isset($_GET['proof_id'])) {
    $pid = intval($_GET['proof_id']);
    try {
        $pdo->beginTransaction();
        $proof = $pdo->prepare("SELECT user_id FROM payment_proofs WHERE id=? AND status='PENDIENTE'"); $proof->execute([$pid]); $p = $proof->fetch();
        if ($p) {
            $pdo->prepare("UPDATE payment_proofs SET status='COMPLETADO' WHERE id=?")->execute([$pid]);
            $pdo->prepare("UPDATE usuarios SET membership_type='PRO', jumper_limit=999999, membership_expires=DATE_ADD(IF(membership_expires>NOW(), membership_expires, NOW()), INTERVAL 30 DAY) WHERE id=?")->execute([$p['user_id']]);
            $pdo->commit(); $message = "Pago aprobado."; $message_type = 'success';
        } else { $pdo->rollBack(); $message = "Pago no encontrado."; $message_type = 'warning'; }
    } catch(Exception $e) { $pdo->rollBack(); $message = "Error: ".$e->getMessage(); $message_type = 'danger'; }
}
if (isset($_GET['action']) && $_GET['action'] == 'reject_payment' && isset($_GET['proof_id'])) {
    $pid = intval($_GET['proof_id']);
    $pdo->prepare("UPDATE payment_proofs SET status='RECHAZADO' WHERE id=? AND status='PENDIENTE'")->execute([$pid]);
    $message = "Pago rechazado."; $message_type = 'warning';
}

// 3. MAPEOS SUBID
if (isset($_POST['add_map']) && $section == 'subid_maps') {
    $p = trim($_POST['projektnummer'] ?? ''); $s = trim($_POST['new_subid'] ?? ''); $c = $_POST['pais'] ?? 'Alemania';
    if (addProjektnummerSubidMap($pdo, $p, $s, $user['id'], $c)) { $message = "Mapeo añadido."; $message_type = 'success'; }
    else { $message = "Error o duplicado."; $message_type = 'danger'; }
}
if (isset($_GET['action']) && $_GET['action'] == 'delete_map' && $section == 'subid_maps') {
    $pdo->prepare("DELETE FROM projektnummer_subid_map WHERE id=?")->execute([$_GET['map_id']]);
    $message = "Mapeo eliminado."; $message_type = 'success';
}
if (isset($_POST['edit_map']) && $section == 'subid_maps') {
    $mapId = intval($_POST['map_id'] ?? 0);
    $newSubid = trim($_POST['new_subid'] ?? '');
    $newPais = $_POST['pais'] ?? 'Alemania';
    if ($mapId > 0 && !empty($newSubid)) {
        try {
            $stmt = $pdo->prepare("UPDATE projektnummer_subid_map SET subid=?, pais=? WHERE id=?");
            if ($stmt->execute([$newSubid, $newPais, $mapId])) { $message = "Mapeo actualizado."; $message_type = 'success'; }
            else { $message = "Error al actualizar."; $message_type = 'danger'; }
        } catch(Exception $e) { $message = "Error DB: ".$e->getMessage(); $message_type = 'danger'; }
    }
}

// 4. LINKS (ACORTADOR)
if (isset($_POST['add_link'])) {
    try { $pdo->prepare("INSERT INTO short_links (slug, target_url) VALUES (?, ?)")->execute([trim($_POST['slug'] ?? ''), trim($_POST['target_url'] ?? '')]); $message="Link creado."; $message_type='success'; }
    catch(Exception $e) { $message="Error: ".$e->getMessage(); $message_type='danger'; }
}
if (isset($_POST['edit_link'])) {
    $slug = trim($_POST['slug'] ?? ''); $target = trim($_POST['target_url'] ?? ''); $id = intval($_POST['link_id'] ?? 0);
    if ($id > 0 && !empty($slug) && !empty($target)) {
        try {
            $stmt = $pdo->prepare("UPDATE short_links SET slug=?, target_url=? WHERE id=?");
            $stmt->execute([$slug, $target, $id]); 
            $message="Link actualizado."; $message_type='success';
        } catch(Exception $e) { $message="Error: ".$e->getMessage(); $message_type='danger'; }
    } else { $message="Datos inválidos."; $message_type='warning'; }
}
if (isset($_GET['action']) && $_GET['action'] == 'delete_link') {
    $pdo->prepare("DELETE FROM short_links WHERE id=?")->execute([$_GET['link_id']]);
    $message="Link eliminado."; $message_type='success';
}

// 5. ACADEMIA
if (isset($_POST['action']) && $_POST['action'] == 'add_modulo') {
    $pdo->prepare("INSERT INTO academia_modulos (titulo, descripcion, orden) VALUES (?, ?, ?)")->execute([$_POST['titulo']??'', $_POST['descripcion']??'', $_POST['orden']??0]);
    $message="Módulo creado."; $message_type='success';
}
if (isset($_POST['action']) && $_POST['action'] == 'edit_modulo') {
    $pdo->prepare("UPDATE academia_modulos SET titulo=?, descripcion=?, orden=? WHERE id=?")->execute([$_POST['titulo']??'', $_POST['descripcion']??'', $_POST['orden']??0, $_POST['modulo_id']??0]);
    $message="Módulo actualizado."; $message_type='success';
}
if (isset($_GET['action']) && $_GET['action'] == 'delete_modulo') {
    $pdo->prepare("DELETE FROM academia_modulos WHERE id=?")->execute([$_GET['id']]);
    $message="Módulo eliminado."; $message_type='success';
}
// EN admin.php (Buscando la sección add_curso)

if (isset($_POST['action']) && $_POST['action'] == 'add_curso') {
    $modulo_id = intval($_POST['modulo_id'] ?? 0);
    
    // --- VALIDACIÓN DE SEGURIDAD ---
    if ($modulo_id <= 0) {
        $message = "Error: No se detectó el Módulo. Refresca la página (Ctrl+F5) e intenta de nuevo.";
        $message_type = 'danger';
    } else {
        try {
            $pdo->prepare("INSERT INTO academia_cursos (modulo_id, titulo, descripcion, tipo, url_contenido, duracion_min, orden) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $modulo_id, 
                    $_POST['titulo']??'', 
                    $_POST['descripcion']??'', 
                    $_POST['tipo']??'texto', 
                    $_POST['url_contenido']??'', 
                    $_POST['duracion_min']??0, 
                    $_POST['orden']??0
                ]);
            $message = "Lección añadida correctamente."; 
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Error DB: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// ============================================================================
// CARGA DE DATOS PARA VISTAS
// ============================================================================
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page - 1) * $perPage;
$dashboardData = []; $tableData = []; $totalItems = 0; 
$modulos = []; $cursos_por_modulo = [];

try {
    if ($section == 'dashboard') {
        $dashboardData['maintenance_mode'] = file_exists('MAINTENANCE');
        $dashboardData['academy_is_disabled'] = file_exists('ACADEMIA_DISABLED');
    } elseif ($section == 'users') {
        $where = $search ? "WHERE username LIKE ?" : "";
        $params = $search ? ["%$search%"] : [];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where"); $stmt->execute($params); $totalItems = $stmt->fetchColumn();
        $sql = "SELECT * FROM usuarios $where ORDER BY online DESC, id DESC LIMIT $perPage OFFSET $offset";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($section == 'payments') {
        $status = $_GET['status'] ?? 'PENDIENTE';
        $where = $status === 'TODOS' ? "1=1" : "p.status = '$status'";
        if ($search) $where .= " AND (u.username LIKE ? OR p.reference_number LIKE ?)";
        $params = $search ? ["%$search%", "%$search%"] : [];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payment_proofs p JOIN usuarios u ON p.user_id=u.id WHERE $where"); $stmt->execute($params); $totalItems = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT p.*, u.username FROM payment_proofs p JOIN usuarios u ON p.user_id=u.id WHERE $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset"); $stmt->execute($params); $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($section == 'academia') {
        try {
            $modulos = $pdo->query("SELECT * FROM academia_modulos ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
            $cursos_stmt = $pdo->query("SELECT * FROM academia_cursos ORDER BY orden ASC");
            while ($c = $cursos_stmt->fetch(PDO::FETCH_ASSOC)) { $cursos_por_modulo[$c['modulo_id']][] = $c; }
        } catch(Exception $e) {}
    } elseif ($section == 'subid_maps') {
        try { $pdo->prepare("UPDATE projektnummer_subid_map SET added_by_user_id = ? WHERE added_by_user_id IS NULL OR added_by_user_id = 0")->execute([$user['id']]); } catch (Exception $e) {}
        $params = [];
        $count_sql = "SELECT COUNT(m.id) FROM projektnummer_subid_map m LEFT JOIN usuarios u ON m.added_by_user_id = u.id";
        $list_sql = "SELECT m.id, m.projektnummer, m.subid, m.pais, m.created_at, m.added_by_user_id, u.username as added_by_username FROM projektnummer_subid_map m LEFT JOIN usuarios u ON m.added_by_user_id = u.id";
        $where = "";
        if ($search) { 
            $search_param = "%$search%"; 
            $where = " WHERE m.projektnummer LIKE ? OR m.subid LIKE ? OR u.username LIKE ?"; 
            $params = [$search_param, $search_param, $search_param];
        }
        $count_sql .= $where;
        $list_sql .= $where . " ORDER BY m.id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($count_sql); $stmt->execute($params); $totalItems = $stmt->fetchColumn();
        $params[] = $perPage; $params[] = $offset;
        $stmt = $pdo->prepare($list_sql); 
        $i = 1; foreach ($params as $param) { $stmt->bindValue($i, $param, is_int($param)?PDO::PARAM_INT:PDO::PARAM_STR); $i++; }
        $stmt->execute(); 
        $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($section == 'shortener') { 
        $params = [];
        $count_sql = "SELECT COUNT(*) FROM short_links";
        $list_sql = "SELECT * FROM short_links";
        if ($search) {
            $search_param = "%$search%";
            $count_sql .= " WHERE slug LIKE ? OR target_url LIKE ?";
            $list_sql .= " WHERE slug LIKE ? OR target_url LIKE ?";
            $params[] = $search_param; $params[] = $search_param;
        }
        $stmt = $pdo->prepare($count_sql); $stmt->execute($params); $totalItems = $stmt->fetchColumn();
        $list_sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($list_sql);
        $bindIndex = 1;
        foreach ($params as $val) { $stmt->bindValue($bindIndex, $val, PDO::PARAM_STR); $bindIndex++; }
        $stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Generic (Logs, Ratings)
        $table = ($section=='logs'?'activity_log':($section=='ratings'?'subid_ratings':'projektnummer_subid_map'));
        $where = ""; 
        $searchParams = [];
        if ($search) {
            if($section=='logs') { $where="WHERE username LIKE ? OR action LIKE ?"; $searchParams = ["%$search%", "%$search%"]; }
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table $where"); $stmt->execute($searchParams); $totalItems = $stmt->fetchColumn();
        $sql = "SELECT * FROM $table $where ORDER BY id DESC LIMIT ? OFFSET ?";
        if ($section == 'ratings') $sql = "SELECT r.*, u.username FROM subid_ratings r LEFT JOIN usuarios u ON r.user_id = u.id $where ORDER BY r.id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql); 
        $bindIndex = 1;
        foreach ($searchParams as $val) { $stmt->bindValue($bindIndex, $val, PDO::PARAM_STR); $bindIndex++; }
        $stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT); 
        $stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute(); $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "Error DB al cargar datos: " . $e->getMessage(); $message_type = 'danger';
}

$totalPages = ceil($totalItems / $perPage);
$paginationBaseUrl = "admin.php?section={$section}" . ($search ? '&search=' . urlencode($search) : '');
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin - Quantum</title>
    <link rel="icon" type="image/png" href="/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter'], display: ['Space Grotesk'] }, colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6', orange: '#F97316', red: '#EF4444' } }, animation: { 'blob': 'blob 7s infinite' }, keyframes: { blob: { '0%': { transform: 'scale(1)' }, '33%': { transform: 'scale(1.1)' }, '66%': { transform: 'scale(0.9)' }, '100%': { transform: 'scale(1)' } } } } }
    }
    </script>
    <link rel="stylesheet" href="new-style.css">
    <style>
        .sidebar-text { opacity: 0; transform: translateX(-10px); transition: all 0.3s ease; white-space: nowrap; position: relative; }
        nav:hover .sidebar-text { opacity: 1; transform: translateX(0); visibility: visible !important; }
        .glass-panel { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); }
        .table { --bs-table-bg: transparent !important; --bs-table-color: inherit !important; color: #E5E7EB !important; }
        .table > :not(caption) > * > * { background-color: transparent !important; color: inherit !important; box-shadow: none !important; border-bottom-color: rgba(255,255,255,0.1) !important; }
        .table tr:hover td { background-color: rgba(255,255,255,0.05) !important; }
        .table th { font-family: 'Space Grotesk'; letter-spacing: 0.05em; color: #9CA3AF !important; font-weight: 600; background-color: rgba(0,0,0,0.3) !important; }
        .modal-content { background-color: #111827; color: white; border: 1px solid rgba(255,255,255,0.1); }
        .btn-close { filter: invert(1); }
        .form-control-dark { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; width: 100%; padding: 0.5rem; border-radius: 0.5rem; }
        .form-control-dark:focus { outline:none; border-color: #30E8BF; }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased overflow-hidden selection:bg-sj-green selection:text-sj-dark">

    <!-- Fondo Aurora -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-sj-orange/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-sj-blue/10 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-30"></div>
    </div>

    <div class="relative z-10 flex h-screen">
        <!-- SIDEBAR -->
        <nav class="hidden md:flex flex-col w-20 hover:w-64 transition-[width] duration-300 ease-out border-r border-white/5 bg-black/20 backdrop-blur-2xl h-full z-50 shadow-2xl overflow-hidden shrink-0 group">
            <div class="h-24 flex items-center px-5 shrink-0 relative">
                <div class="w-10 h-10 bg-sj-orange/20 rounded-xl flex items-center justify-center text-sj-orange shrink-0 shadow-lg shadow-sj-orange/10"><i class="bi bi-shield-lock-fill text-xl"></i></div>
                <div class="sidebar-text ml-4"><span class="font-display font-bold text-xl text-white block">Admin Panel</span><span class="text-[10px] text-sj-orange tracking-widest uppercase font-medium">Quantum Core</span></div>
            </div>
            <div class="flex-1 overflow-y-auto py-6 space-y-2 px-3 custom-scrollbar">
                <?php foreach(['dashboard'=>['bi-grid-1x2-fill','Dashboard'],'users'=>['bi-people-fill','Usuarios'],'payments'=>['bi-wallet-fill','Pagos'],'academia'=>['bi-mortarboard-fill','Academia'],'subid_maps'=>['bi-database-fill','Mapeos DB'],'shortener'=>['bi-link-45deg','Acortador'],'ratings'=>['bi-star-fill','Ratings'],'logs'=>['bi-clipboard-data-fill','Logs']] as $k=>$v): $act=($section===$k); ?>
                <a href="admin.php?section=<?=$k?>" class="flex items-center h-12 rounded-xl transition-all duration-200 relative group/item overflow-hidden <?=$act?'bg-gradient-to-r from-sj-orange/20 to-transparent text-white border-l-2 border-sj-orange':'text-gray-400 hover:text-white hover:bg-white/5'?>">
                    <div class="w-14 flex justify-center items-center shrink-0"><i class="bi <?=$v[0]?> text-lg <?=$act?'text-sj-orange':''?>"></i></div><span class="sidebar-text font-medium text-sm tracking-wide"><?=$v[1]?></span>
                    <?php if($k=='payments'): $pc=$pdo->query("SELECT COUNT(*) FROM payment_proofs WHERE status='PENDIENTE'")->fetchColumn(); if($pc>0): ?><span class="sidebar-text ml-auto mr-3 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?=$pc?></span><?php endif; endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t border-white/5 bg-black/20 mx-2 mb-2 rounded-xl">
                <a href="dashboard.php" class="flex items-center h-10 rounded-lg text-sj-green hover:bg-sj-green/10 transition-all mb-1"><div class="w-10 flex justify-center items-center shrink-0"><i class="bi bi-arrow-left-circle-fill"></i></div><span class="sidebar-text ml-2 font-medium text-sm">Volver a App</span></a>
                <a href="logout.php" class="flex items-center h-10 rounded-lg text-red-400 hover:bg-red-500/10 transition-all"><div class="w-10 flex justify-center items-center shrink-0"><i class="bi bi-power"></i></div><span class="sidebar-text ml-2 font-medium text-sm">Salir</span></a>
            </div>
        </nav>

        <!-- CONTENIDO -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-20 px-8 flex items-center justify-between z-40 bg-sj-dark/50 backdrop-blur-sm border-b border-white/5">
                <h1 class="font-display text-2xl font-bold text-white capitalize"><?= str_replace('_', ' ', $section) ?></h1>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden md:block"><div class="text-sm font-bold text-white">Administrador</div><div class="text-xs text-gray-500">Superuser</div></div>
                    <img src="https://api.dicebear.com/8.x/bottts/svg?seed=admin" class="w-10 h-10 rounded-full bg-sj-card border border-white/20">
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border <?= $message_type=='success'?'bg-green-500/10 border-green-500/50 text-green-400':($message_type=='danger'?'bg-red-500/10 border-red-500/50 text-red-400':'bg-blue-500/10 border-blue-500/50 text-blue-400') ?>">
                        <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($section == 'dashboard'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <?php $stats=[['Usuarios','stat-total-users','white','people-fill'],['Online','stat-online-users','sj-green','circle-fill'],['Jumpers (7d)','stat-total-jumpers','sj-blue','graph-up'],['Login (7d)','stat-total-logins','sj-orange','key-fill']]; 
                        foreach($stats as $s): ?>
                        <div class="glass-panel p-6 rounded-2xl relative overflow-hidden">
                            <div class="text-gray-400 text-xs uppercase mb-1"><?=$s[0]?></div><div class="text-3xl font-display font-bold text-<?=$s[2]?>" id="<?=$s[1]?>">...</div>
                            <i class="bi bi-<?=$s[3]?> absolute top-4 right-4 text-4xl opacity-10 text-<?=$s[2]?>"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 glass-panel p-6 rounded-2xl">
                            <h3 class="text-lg font-bold text-white mb-4">Actividad</h3><div class="h-64 w-full"><canvas id="admin-chart"></canvas></div>
                        </div>
                        <div class="glass-panel p-6 rounded-2xl">
                            <h3 class="text-lg font-bold text-white mb-4">Control</h3>
                            <div class="space-y-3">
                                <form id="maintenance-form"><input type="hidden" name="action" value="toggle_maintenance"><input type="hidden" name="value" value="<?= $dashboardData['maintenance_mode']?'off':'on' ?>"><button class="w-full py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-white flex justify-center gap-2"><i class="bi bi-power"></i> <?= $dashboardData['maintenance_mode']?'Desactivar Mant.':'Activar Mant.' ?></button></form>
                                <form id="purge-cache-form"><input type="hidden" name="action" value="purge_cache"><button class="w-full py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-white flex justify-center gap-2"><i class="bi bi-cloud-slash"></i> Purgar Caché</button></form>
                                <form id="academy-form"><input type="hidden" name="action" value="toggle_academy"><input type="hidden" name="value" value="<?= $dashboardData['academy_is_disabled']?'off':'on' ?>"><button class="w-full py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-white flex justify-center gap-2"><i class="bi bi-mortarboard"></i> <?= $dashboardData['academy_is_disabled']?'Activar Academia':'Suspender Academia' ?></button></form>
                                <form id="clear-logs-form"><input type="hidden" name="action" value="clear_logs"><button class="w-full py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-white flex justify-center gap-2"><i class="bi bi-trash3"></i> Limpiar Logs</button></form>
                                <form id="force-logout-form"><input type="hidden" name="action" value="force_logout"><button class="w-full py-2 rounded-lg border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-400 flex justify-center gap-2"><i class="bi bi-power"></i> Forzar Logout</button></form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($section == 'users'): ?>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                            <form class="flex"><input type="hidden" name="section" value="users"><input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Buscar..." class="form-control-dark rounded-l-lg w-64"><button class="bg-sj-blue px-3 rounded-r-lg text-white"><i class="bi bi-search"></i></button></form>
                            <button class="bg-sj-green text-sj-dark px-4 py-1 rounded-lg font-bold text-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">Nuevo</button>
                        </div>
                        <table class="table w-full text-left text-sm">
                            <thead><tr><th class="p-3">Usuario</th><th class="p-3">Plan</th><th class="p-3">Estado</th><th class="p-3">Jumpers</th><th class="p-3">Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach($tableData as $r): ?>
                                <tr>
                                    <td class="p-3 font-bold"><?= htmlspecialchars($r['username']) ?></td>
                                    <td class="p-3"><span class="px-2 py-1 rounded text-xs font-bold bg-white/10 text-white"><?= $r['membership_type'] ?></span></td>
                                    <td class="p-3">
                                        <?php if($r['online'] == 1): ?>
                                            <span class="text-sj-green font-bold">● En Línea</span>
                                        <?php elseif ($r['active'] == 0): ?>
                                            <span class="text-red-400">Inactivo</span>
                                        <?php else: ?>
                                            <span class="text-gray-500">Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-gray-400"><?= $r['jumper_count'] ?> / <?= $r['jumper_limit'] ?></td>
                                    <td class="p-3">
                                        <button class="text-blue-400 hover:text-white mr-2" data-bs-toggle="modal" data-bs-target="#editUserModal-<?= $r['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                        <a href="admin.php?section=users&action=delete_user&user_id=<?= $r['id'] ?>" class="text-red-400 hover:text-white" onclick="return confirm('Eliminar?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= paginationLinks($page, $totalPages, $paginationBaseUrl) ?>

                <?php elseif ($section == 'payments'): ?>
                     <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                             <div class="flex gap-2"><a href="admin.php?section=payments&status=PENDIENTE" class="text-xs px-3 py-1 rounded-full bg-yellow-500/20 text-yellow-500 border border-yellow-500/50">Pendientes</a><a href="admin.php?section=payments&status=TODOS" class="text-xs px-3 py-1 rounded-full bg-white/5 text-gray-400 border border-white/10">Todos</a></div>
                        </div>
                        <table class="table w-full text-left text-sm">
                            <thead><tr><th class="p-3">Usuario</th><th class="p-3">Ref</th><th class="p-3">Img</th><th class="p-3">Estado</th><th class="p-3">Acción</th></tr></thead>
                            <tbody>
                                <?php foreach($tableData as $r): ?>
                                <tr>
                                    <td class="p-3"><?= htmlspecialchars($r['username']) ?></td>
                                    <td class="p-3 font-mono text-gray-400"><?= htmlspecialchars($r['reference_number']) ?></td>
                                    <td class="p-3"><a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="text-sj-blue hover:underline">Ver</a></td>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-bold <?= $r['status']=='PENDIENTE'?'bg-yellow-500/20 text-yellow-500':($r['status']=='COMPLETADO'?'bg-green-500/20 text-green-500':'bg-red-500/20 text-red-500') ?>"><?= $r['status'] ?></span></td>
                                    <td class="p-3">
                                        <?php if($r['status']=='PENDIENTE'): ?>
                                        <a href="admin.php?section=payments&action=approve_payment&proof_id=<?=$r['id']?>" class="text-green-400 hover:text-white mr-3"><i class="bi bi-check-lg text-lg"></i></a>
                                        <a href="admin.php?section=payments&action=reject_payment&proof_id=<?=$r['id']?>" class="text-red-400 hover:text-white"><i class="bi bi-x-lg text-lg"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                     <?= paginationLinks($page, $totalPages, $paginationBaseUrl) ?>

                <?php elseif ($section == 'academia'): ?>
                     <div class="glass-panel rounded-2xl p-6">
                        <div class="flex justify-between mb-4">
                            <h3 class="text-lg font-bold text-white">Módulos</h3>
                            <button class="btn btn-success bg-sj-green text-sj-dark px-3 py-1 rounded" data-bs-toggle="modal" data-bs-target="#addModuloModal">Crear Módulo</button>
                        </div>
                        <?php foreach($modulos as $m): ?>
                            <div class="border border-white/10 rounded-xl p-4 mb-4 bg-black/20">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-bold text-white"><?= htmlspecialchars($m['titulo']) ?></h4>
                                    <div>
                                        <button class="text-blue-400 mr-2" 
        data-bs-toggle="modal" 
        data-bs-target="#addCursoModal" 
        data-modulo-id="<?=$m['id']?>"  <-- ¡ESTO DEBE ESTAR ASÍ!
        data-modulo-titulo="<?=htmlspecialchars($m['titulo'])?>">
    <i class="bi bi-plus-circle"></i> Lección
</button>
                                        <a href="admin.php?section=academia&action=delete_modulo&id=<?=$m['id']?>" class="text-red-400" onclick="return confirm('Borrar módulo?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </div>
                                <?php if(!empty($cursos_por_modulo[$m['id']])): ?>
                                    <table class="table w-full text-xs">
                                        <thead><tr><th>Orden</th><th>Lección</th><th>Tipo</th><th>Acción</th></tr></thead>
                                        <tbody>
                                            <?php foreach($cursos_por_modulo[$m['id']] as $c): ?>
                                            <tr>
                                                <td><?=$c['orden']?></td>
                                                <td><?=htmlspecialchars($c['titulo'])?></td>
                                                <td><?=$c['tipo']?></td>
                                                <td><a href="admin.php?section=academia&action=delete_curso&id=<?=$c['id']?>" class="text-red-400"><i class="bi bi-x-lg"></i></a></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?><p class="text-gray-500 text-xs">Sin lecciones.</p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                     </div>

                <?php elseif ($section == 'subid_maps'): ?>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                            <form class="flex"><input type="hidden" name="section" value="subid_maps"><input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Buscar Projekt..." class="form-control-dark rounded-l-lg w-64"><button class="bg-sj-blue px-3 rounded-r-lg text-white"><i class="bi bi-search"></i></button></form>
                            <button class="btn btn-success px-4 py-1 rounded" data-bs-toggle="modal" data-bs-target="#addMapModal">Añadir Manual</button>
                        </div>
                        <table class="table w-full text-left text-sm">
                            <thead><tr><th>Projektnummer</th><th>SubID</th><th>País</th><th>Añadido por</th><th>Fecha</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($tableData as $row): ?>
                                    <tr>
                                        <td class="p-3 font-bold text-white"><?= htmlspecialchars($row['projektnummer']) ?></td>
                                        <td class="p-3 font-mono text-sj-green"><?= htmlspecialchars($row['subid']) ?></td>
                                        <td class="p-3"><span class="badge bg-white/10"><?= htmlspecialchars($row['pais']??'Alemania') ?></span></td>
                                        <td class="p-3 text-gray-400"><?= htmlspecialchars($row['added_by_username'] ?? 'admin') ?></td>
                                        <td class="p-3 text-gray-500"><?= (new DateTime($row['created_at']))->format('d/m/y') ?></td>
                                        <td class="p-3">
                                            <button class="text-blue-400 mr-2" data-bs-toggle="modal" data-bs-target="#editMapModal-<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                                            <a href="admin.php?section=subid_maps&action=delete_map&map_id=<?= $row['id'] ?>" class="text-red-400" onclick="return confirm('Borrar?')"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= paginationLinks($page, $totalPages, $paginationBaseUrl) ?>
                
                <?php elseif ($section == 'shortener'): ?>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                            <form class="flex"><input type="hidden" name="section" value="shortener"><input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Buscar Link..." class="form-control-dark rounded-l-lg w-64"><button class="bg-sj-blue px-3 rounded-r-lg text-white"><i class="bi bi-search"></i></button></form>
                            <button class="btn btn-success px-4 py-1 rounded" data-bs-toggle="modal" data-bs-target="#addLinkModal">Crear Link</button>
                        </div>
                        <table class="table w-full text-left text-sm">
                            <thead><tr><th>Atajo</th><th>Destino</th><th>Fecha</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($tableData as $row): ?>
                                    <tr>
                                        <td class="p-3 font-bold text-white">/go/<?= htmlspecialchars($row['slug']) ?></td>
                                        <td class="p-3 text-gray-400 truncate max-w-xs"><?= htmlspecialchars($row['target_url']) ?></td>
                                        <td class="p-3 text-gray-500"><?= (new DateTime($row['created_at']))->format('d/m/y') ?></td>
                                        <td class="p-3">
                                            <button class="text-blue-400 mr-2" data-bs-toggle="modal" data-bs-target="#editLinkModal-<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                                            <a href="admin.php?section=shortener&action=delete_link&link_id=<?= $row['id'] ?>" class="text-red-400" onclick="return confirm('Borrar?')"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= paginationLinks($page, $totalPages, $paginationBaseUrl) ?>

                <?php else: ?>
                    <!-- Fallback (Logs, Ratings) -->
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                            <h3 class="font-bold text-white capitalize"><?= str_replace('_',' ',$section) ?></h3>
                            <form class="flex"><input type="hidden" name="section" value="<?=$section?>"><input type="text" name="search" value="<?=htmlspecialchars($search)?>" class="form-control-dark rounded-l-lg"><button class="bg-sj-blue px-3 rounded-r-lg text-white"><i class="bi bi-search"></i></button></form>
                        </div>
                        <table class="table w-full text-left text-sm">
                            <thead><tr>
                                <?php if(!empty($tableData)) foreach(array_keys($tableData[0]) as $k) echo "<th class='p-3'>".ucfirst($k)."</th>"; ?>
                            </tr></thead>
                            <tbody>
                                <?php foreach($tableData as $r): ?>
                                <tr><?php foreach($r as $v) echo "<td class='p-3'>".htmlspecialchars(substr($v,0,50))."</td>"; ?></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= paginationLinks($page, $totalPages, $paginationBaseUrl) ?>
                <?php endif; ?>

            </main>
        </div>
    </div>
    
    <!-- MODALES ADMIN (Con el nuevo campo de Fecha) -->
    <div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Nuevo Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=users"><input type="hidden" name="add_user" value="1"><input type="text" name="new_username" class="form-control-dark mb-3" placeholder="Usuario" required><input type="password" name="new_password" class="form-control-dark mb-3" placeholder="Password" required><select name="membership_type" class="form-control-dark mb-3"><option value="PRUEBA GRATIS">Prueba</option><option value="PRO">PRO</option><option value="ADMINISTRADOR">Admin</option></select><button class="w-full bg-sj-green text-sj-dark font-bold py-2 rounded">Crear</button></form></div></div></div></div>
    
    <?php if ($section == 'users' && !empty($tableData)): foreach ($tableData as $r): ?>
    <div class="modal fade" id="editUserModal-<?= $r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Editar <?=htmlspecialchars($r['username'])?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=users"><input type="hidden" name="edit_user" value="1"><input type="hidden" name="user_id" value="<?=$r['id']?>">
        
        <!-- Selector de Membresía Actualizado -->
        <label class="text-sm text-gray-400">Membresía</label>
        <select name="membership_type" class="form-control-dark mb-3">
            <option value="PRUEBA GRATIS" <?=$r['membership_type']=='PRUEBA GRATIS'?'selected':''?>>PRUEBA GRATIS</option>
            <option value="PRO" <?=$r['membership_type']=='PRO'?'selected':''?>>PRO</option>
            <option value="ADMINISTRADOR" <?=$r['membership_type']=='ADMINISTRADOR'?'selected':''?>>ADMINISTRADOR</option>
            <option value="VENCIDO" <?=$r['membership_type']=='VENCIDO'?'selected':''?>>VENCIDO</option>
        </select>

        <!-- Campo de Fecha de Vencimiento (Opcional) -->
        <label class="text-sm text-gray-400">Vence (Opcional)</label>
        <input type="text" name="membership_expires" value="<?=$r['membership_expires']?>" class="form-control-dark mb-3" placeholder="YYYY-MM-DD HH:MM:SS">
        
        <div class="flex gap-2 mb-3"><input type="number" name="jumper_count" value="<?=$r['jumper_count']?>" class="form-control-dark" placeholder="Usados"><input type="number" name="jumper_limit" value="<?=$r['jumper_limit']?>" class="form-control-dark" placeholder="Límite"></div>
        
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="academia_privilege" value="1" <?= ($r['banned'] == 1 || $r['membership_type'] == 'ADMINISTRADOR') ? 'checked' : '' ?>> <label class="text-gray-400">Acceso Academia</label></div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="academia_privilege" value="1" <?= ($r['banned'] == 1 || $r['membership_type'] == 'ADMINISTRADOR') ? 'checked' : '' ?>> 
            <label class="text-gray-400">Acceso Academia</label>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_multisession" value="1" <?= ($r['allow_multisession'] ?? 0) == 1 ? 'checked' : '' ?>> 
            <label class="text-sj-green font-bold">Permitir Multisesión (Farms)</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="active" value="1" <?=$r['active']?'checked':''?>> 
            <label class="text-gray-400">Activo</label>
        </div>
        
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="active" value="1" <?=$r['active']?'checked':''?>> <label class="text-gray-400">Activo</label></div>
        
        <button class="w-full bg-sj-blue text-white font-bold py-2 rounded">Guardar</button></form></div></div></div></div>
    <?php endforeach; endif; ?>

    <!-- (Otros modales sin cambios...) -->
    <div class="modal fade" id="addMapModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Añadir Mapeo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=subid_maps"><input type="hidden" name="add_map" value="1"><input type="text" name="projektnummer" class="form-control-dark mb-3" placeholder="Projektnummer" required pattern="\d{5,6}"><input type="text" name="new_subid" class="form-control-dark mb-3" placeholder="SubID" required><select name="pais" class="form-control-dark mb-3"><option value="Alemania">Alemania</option><option value="Austria">Austria</option><option value="Suiza">Suiza</option></select><button class="w-full bg-sj-green text-sj-dark font-bold py-2 rounded">Añadir</button></form></div></div></div></div>
    
    <?php if ($section == 'subid_maps' && !empty($tableData)): foreach ($tableData as $row): ?>
        <div class="modal fade" id="editMapModal-<?= $row['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Editar Mapeo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=subid_maps"><input type="hidden" name="edit_map" value="1"><input type="hidden" name="map_id" value="<?= $row['id'] ?>"><div class="mb-3"><label class="form-label">Projektnummer</label><input type="text" class="form-control-dark" value="<?= htmlspecialchars($row['projektnummer']) ?>" disabled></div><div class="mb-3"><label class="form-label">SubID</label><input type="text" class="form-control-dark" name="new_subid" value="<?= htmlspecialchars($row['subid']) ?>" required></div><div class="mb-3"><label class="form-label">País</label><select class="form-control-dark" name="pais"><option value="Alemania" <?= ($row['pais']??'Alemania')=='Alemania'?'selected':'' ?>>Alemania</option><option value="Austria" <?= ($row['pais']??'')=='Austria'?'selected':'' ?>>Austria</option><option value="Suiza" <?= ($row['pais']??'')=='Suiza'?'selected':'' ?>>Suiza</option></select></div><button type="submit" class="btn btn-primary w-100">Guardar</button></form></div></div></div></div>
    <?php endforeach; endif; ?>

    <!-- Modal Añadir Link -->
    <div class="modal fade" id="addLinkModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Nuevo Enlace</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=shortener"><input type="hidden" name="add_link" value="1"><div class="mb-3"><label>Slug</label><div class="input-group"><span class="input-group-text bg-black border-secondary text-white">/go/</span><input type="text" class="form-control-dark" name="slug" required pattern="[a-zA-Z0-9_-]+"></div></div><div class="mb-3"><label>URL</label><input type="url" class="form-control-dark" name="target_url" required></div><button type="submit" class="btn btn-success w-100">Crear</button></form></div></div></div></div>

    <!-- Modales Edit para Links -->
    <?php if ($section == 'shortener' && !empty($tableData)): foreach ($tableData as $row): ?>
        <div class="modal fade" id="editLinkModal-<?= $row['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content glass-panel bg-sj-dark"><div class="modal-header border-b border-white/10"><h5 class="text-white">Editar Enlace</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="admin.php?section=shortener"><input type="hidden" name="edit_link" value="1"><input type="hidden" name="link_id" value="<?= $row['id'] ?>"><div class="mb-3"><label class="form-label">Slug</label><input type="text" class="form-control-dark" name="slug" value="<?= htmlspecialchars($row['slug']) ?>" required></div><div class="mb-3"><label class="form-label">URL</label><input type="url" class="form-control-dark" name="target_url" value="<?= htmlspecialchars($row['target_url']) ?>" required></div><button type="submit" class="btn btn-primary w-100">Guardar</button></form></div></div></div></div>
    <?php endforeach; endif; ?>
<div class="modal fade" id="addModuloModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-panel bg-sj-dark">
                <div class="modal-header border-b border-white/10">
                    <h5 class="text-white font-bold">Nuevo Módulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="admin.php?section=academia">
                        <input type="hidden" name="action" value="add_modulo">
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Título del Módulo</label>
                            <input type="text" name="titulo" class="form-control-dark" placeholder="Ej: Introducción a Encuestas" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Descripción</label>
                            <textarea name="descripcion" class="form-control-dark" rows="2" placeholder="Breve resumen..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Orden</label>
                            <input type="number" name="orden" class="form-control-dark" value="1">
                        </div>

                        <button class="w-full bg-sj-green text-sj-dark font-bold py-2 rounded hover:bg-emerald-400 transition-colors">Crear Módulo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCursoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-panel bg-sj-dark">
                <div class="modal-header border-b border-white/10">
                    <h5 class="text-white font-bold">Nueva Lección</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="admin.php?section=academia">
                        <input type="hidden" name="action" value="add_curso">
                        <input type="hidden" name="modulo_id" id="modal-input-modulo-id">
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Título</label>
                            <input type="text" name="titulo" class="form-control-dark" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <label class="text-gray-400 text-xs uppercase font-bold">Tipo</label>
                                <select name="tipo" class="form-control-dark">
                                    <option value="video">Video (MP4/YouTube)</option>
                                    <option value="pdf">Documento PDF</option>
                                    <option value="texto">Texto / Artículo</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-gray-400 text-xs uppercase font-bold">Duración (min)</label>
                                <input type="number" name="duracion_min" class="form-control-dark" value="5">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">URL del Contenido</label>
                            <input type="text" name="url_contenido" class="form-control-dark" placeholder="https://..." required>
                            <p class="text-xs text-gray-500 mt-1">Pega aquí el link directo del video, PDF o iframe de YouTube.</p>
                        </div>

                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Descripción</label>
                            <textarea name="descripcion" class="form-control-dark" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-gray-400 text-xs uppercase font-bold">Orden</label>
                            <input type="number" name="orden" class="form-control-dark" value="1">
                        </div>

                        <button class="w-full bg-sj-blue text-white font-bold py-2 rounded hover:bg-blue-600 transition-colors">Añadir Lección</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="admin-script.js?v=30.0"></script>
</body>
</html>