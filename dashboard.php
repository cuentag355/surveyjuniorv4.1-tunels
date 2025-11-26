<?php
// dashboard.php (v40.0 - Quantum Core Stable)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';
require 'functions.php';

// --- 1. VALIDACIÓN DE SESIÓN MAESTRA ---
if (!isset($_SESSION['user'])) {
    if (function_exists('validateRememberMe')) { 
        $userFromCookie = validateRememberMe($pdo); 
        if (!$userFromCookie) { header('Location: login.php'); exit; }
    } else { header('Location: login.php'); exit; }
}
$user = $_SESSION['user'];

// Validación de Token y Multisesión
if (!isSessionValid($pdo, $user)) {
    session_unset(); 
    session_destroy(); 
    header("Location: login.php?error=Sesión+duplicada"); 
    exit;
}

// Datos globales
$membership_type = $user['membership_type'] ?? 'VENCIDO';
$can_use_generators = ($membership_type === 'ADMINISTRADOR' || $membership_type === 'PRO' || $membership_type === 'PRUEBA GRATIS');
$academy_is_disabled = file_exists(__DIR__ . '/ACADEMIA_DISABLED');
$show_academia_module = (in_array($membership_type, ['ADMINISTRADOR', 'PRO']) && !$academy_is_disabled);
$show_chatbot_module = (in_array($membership_type, ['ADMINISTRADOR', 'PRO']));

// --- CARGADOR AJAX DE MÓDULOS ---
$isFragmentRequest = isset($_GET['fetch']) && $_GET['fetch'] === 'fragment';

if ($isFragmentRequest) {
    
    // BLOQUEO DE MANTENIMIENTO
    if (file_exists('MAINTENANCE') && $user['membership_type'] !== 'ADMINISTRADOR') {
        include('modules/maintenance.php');
        exit;
    }

    $module = $_GET['module'] ?? 'home';
    // Lista blanca estricta de módulos permitidos
    $allowed_modules = [
        'home', 'ranking', 'membership', 'shortener', 'academia', 'chatbot', 
        'opensurvey', 'meinungsplatz', 'samplicio', 'dkr', 'spectrum', 
        'cint', 'horizoom', 'marketmind', 'opinionexchange', 'm3global', 
        'maintenance'
    ];
    
    $module_path = "modules/{$module}.php";
    
    // Datos disponibles para los módulos
    $view_data = ['user' => $user, 'pdo' => $pdo, 'can_use_generators' => $can_use_generators];
    
    // Validación de acceso a Academia
    if ($module === 'academia' && !$show_academia_module) { 
        http_response_code(403); 
        echo "<div class='glass-panel p-6 text-red-400 border-l-4 border-red-500'>Acceso Denegado a Academia</div>"; 
        exit; 
    }

    // Carga del archivo
    if (in_array($module, $allowed_modules)) {
        if (file_exists($module_path)) {
            include($module_path);
        } else {
            // Fallback para módulos en construcción
            include('modules/construction.php');
        }
    } else {
        http_response_code(404);
        echo "<div class='glass-panel p-6 text-red-400 border-l-4 border-red-500'>Módulo no encontrado: " . htmlspecialchars($module) . "</div>";
    }
    exit; 
}

$module = $_GET['module'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="referrer" content="no-referrer">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>SurveyJunior Quantum</title>
    
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] },
                    colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6', orange: '#F97316', yellow: '#FACC15' } },
                    animation: { 'blob': 'blob 7s infinite', 'fade-in': 'fadeIn 0.5s ease-out' },
                    keyframes: {
                        blob: { '0%': { transform: 'scale(1)' }, '33%': { transform: 'scale(1.1)' }, '66%': { transform: 'scale(0.9)' }, '100%': { transform: 'scale(1)' } },
                        fadeIn: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="new-style.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(48, 232, 191, 0.4); }
        nav:hover .sidebar-text { opacity: 1 !important; transform: translateX(0) !important; visibility: visible !important; }
        .sidebar-text { opacity: 0; transform: translateX(-10px); transition: all 0.3s ease-in-out; transition-delay: 0.1s; white-space: nowrap; visibility: hidden; position: relative; }
        .glass-panel { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased selection:bg-sj-green selection:text-sj-dark overflow-hidden">

    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-sj-purple/20 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-sj-blue/20 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-30"></div>
    </div>

    <div class="relative z-10 flex h-screen" id="app-shell">
        <nav class="hidden md:flex flex-col w-20 hover:w-64 transition-[width] duration-300 ease-out border-r border-white/5 bg-black/20 backdrop-blur-2xl h-full z-50 shadow-[4px_0_24px_0_rgba(0,0,0,0.5)] overflow-hidden shrink-0">
            <div class="h-24 flex items-center px-5 shrink-0 relative">
                <div class="absolute left-0 w-20 h-full flex justify-center items-center pointer-events-none"><div class="w-10 h-10 bg-sj-green/10 rounded-full blur-lg opacity-0 hover:opacity-100 transition-opacity duration-500"></div></div>
                <svg class="w-10 h-10 text-sj-green shrink-0 relative z-10 drop-shadow-[0_0_8px_rgba(48,232,191,0.6)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4" stroke-opacity="0.8"/><path d="M16 8h-6"/><path d="M8 16h6"/></svg>
                <div class="sidebar-text ml-4"><span class="font-display font-bold text-xl text-white tracking-wide block">SurveyJunior</span><span class="text-[10px] text-sj-green block tracking-widest uppercase font-medium">Quantum</span></div>
            </div>
            <div class="flex-1 overflow-y-auto py-4 space-y-2 px-3 custom-scrollbar overflow-x-hidden">
                <?php
                $menuItems = [['module'=>'home','icon'=>'bi-grid-1x2-fill','label'=>'Dashboard'],['module'=>'ranking','icon'=>'bi-trophy-fill','label'=>'Ranking'],['module'=>'membership','icon'=>'bi-gem','label'=>'Membresía'],['module'=>'shortener','icon'=>'bi-link-45deg','label'=>'Links Útiles']];
                if($show_academia_module) $menuItems[]=['module'=>'academia','icon'=>'bi-mortarboard-fill','label'=>'Academia'];
                if($show_chatbot_module) $menuItems[]=['module'=>'chatbot','icon'=>'bi-robot','label'=>'Asistente IA'];
                foreach($menuItems as $item): $isActive=($module===$item['module']); ?>
                    <a href="dashboard.php?module=<?=$item['module']?>" class="nav-link flex items-center h-12 rounded-xl transition-all duration-200 relative overflow-hidden <?=$isActive?'bg-gradient-to-r from-sj-green/10 to-transparent text-white':'text-gray-400 hover:text-white hover:bg-white/5'?>" title="<?=$item['label']?>">
                        <?php if($isActive): ?><div class="absolute left-0 top-2 bottom-2 w-1 bg-sj-green rounded-r-full shadow-[0_0_10px_#30E8BF]"></div><?php endif; ?>
                        <div class="w-14 flex justify-center items-center shrink-0"><i class="bi <?=$item['icon']?> text-xl transition-transform duration-300 hover:scale-110 <?=$isActive?'text-sj-green drop-shadow-[0_0_5px_rgba(48,232,191,0.8)]':''?>"></i></div>
                        <span class="sidebar-text font-medium text-sm tracking-wide"><?=$item['label']?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t border-white/5 bg-black/20 backdrop-blur-md mx-2 mb-2 rounded-xl overflow-hidden shrink-0">
                <div class="flex flex-col gap-1">
                    <?php if ($user['membership_type'] === 'ADMINISTRADOR'): ?>
                    <a href="admin.php" class="flex items-center h-10 rounded-lg text-sj-orange hover:bg-sj-orange/10 hover:text-white transition-all"><div class="w-10 flex justify-center items-center shrink-0"><i class="bi bi-shield-lock-fill text-lg"></i></div><span class="sidebar-text ml-2 font-medium text-sm">Admin</span></a>
                    <?php endif; ?>
                    <a href="logout.php" class="flex items-center h-10 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all"><div class="w-10 flex justify-center items-center shrink-0"><i class="bi bi-box-arrow-left text-lg"></i></div><span class="sidebar-text ml-2 font-medium text-sm">Cerrar Sesión</span></a>
                </div>
            </div>
        </nav>

        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-16 md:h-20 px-4 md:px-8 flex items-center justify-between z-40 bg-sj-dark/50 backdrop-blur-sm md:bg-transparent">
                <div class="md:hidden flex items-center gap-2">
                    <svg class="w-6 h-6 text-sj-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M16 8h-6"/><path d="M8 16h6"/></svg><span class="font-display font-bold text-lg text-white">SurveyJunior</span>
                </div>
                <div class="hidden md:flex flex-1 justify-end items-center gap-4">
                    <a href="https://t.me/surveyjuniorus" target="_blank" class="flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-orange-500 to-yellow-500 text-white font-bold text-sm shadow-lg shadow-orange-500/20 hover:scale-105 transition-transform"><i class="bi bi-fire animate-pulse"></i> Contactar Admin</a>
                    <div class="flex items-center gap-3 pl-4 border-l border-white/10">
                        <div class="text-right"><div class="text-sm font-bold text-white"><?= htmlspecialchars($user['username']) ?></div><div class="text-xs text-sj-green font-medium"><?= htmlspecialchars($user['membership_type']) ?></div></div>
                        <img id="user-avatar-btn" src="https://api.dicebear.com/8.x/adventurer/svg?seed=<?= urlencode($user['username']) ?>" class="w-10 h-10 rounded-full border-2 border-white/10 cursor-pointer hover:border-sj-green bg-sj-card">
                    </div>
                </div>
                <div class="md:hidden"><img id="user-avatar-btn-mobile" src="https://api.dicebear.com/8.x/adventurer/svg?seed=<?= urlencode($user['username']) ?>" class="w-8 h-8 rounded-full border border-white/20"></div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 pb-28 md:pb-8 custom-scrollbar scroll-smooth relative" id="module-content">
                <div class="flex justify-center items-center h-full"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sj-green"></div></div>
            </main>
            
            <nav class="md:hidden fixed bottom-4 left-4 right-4 h-16 bg-sj-card/90 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl z-50 flex justify-between items-center px-6">
                <a href="dashboard.php?module=home" class="nav-link flex flex-col items-center gap-1 text-gray-400 hover:text-sj-green transition-colors <?= ($module==='home')?'text-sj-green':'' ?>"><i class="bi bi-grid-1x2-fill text-xl"></i><span class="text-[10px] font-medium">Inicio</span></a>
                <a href="dashboard.php?module=ranking" class="nav-link flex flex-col items-center gap-1 text-gray-400 hover:text-sj-green transition-colors <?= ($module==='ranking')?'text-sj-green':'' ?>"><i class="bi bi-trophy-fill text-xl"></i><span class="text-[10px] font-medium">Top</span></a>
                <div class="relative -top-6"><button class="w-14 h-14 rounded-full bg-gradient-to-br from-sj-green to-sj-blue flex items-center justify-center text-sj-dark shadow-lg shadow-sj-green/30 border-4 border-sj-dark" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"><i class="bi bi-lightning-charge-fill text-2xl"></i></button></div>
                <a href="dashboard.php?module=membership" class="nav-link flex flex-col items-center gap-1 text-gray-400 hover:text-sj-green transition-colors <?= ($module==='membership')?'text-sj-green':'' ?>"><i class="bi bi-gem text-xl"></i><span class="text-[10px] font-medium">Plan</span></a>
                <?php if ($show_chatbot_module): ?>
                <a href="dashboard.php?module=chatbot" class="nav-link flex flex-col items-center gap-1 text-gray-400 hover:text-sj-green transition-colors <?= ($module==='chatbot')?'text-sj-green':'' ?>"><i class="bi bi-robot text-xl"></i><span class="text-[10px] font-medium">IA</span></a>
                <?php else: ?>
                <a href="logout.php" class="nav-link flex flex-col items-center gap-1 text-gray-400 hover:text-red-400 transition-colors"><i class="bi bi-box-arrow-right text-xl"></i><span class="text-[10px] font-medium">Salir</span></a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <div class="offcanvas offcanvas-bottom h-auto rounded-t-3xl bg-sj-dark border-t border-white/10 text-white" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header justify-center pb-0"><div class="w-12 h-1 bg-white/10 rounded-full"></div></div>
        <div class="offcanvas-body p-6 grid grid-cols-3 gap-4">
            <a href="dashboard.php?module=academia" class="nav-link flex flex-col items-center gap-2 p-4 rounded-xl bg-white/5 hover:bg-white/10"><i class="bi bi-mortarboard-fill text-2xl text-sj-purple"></i><span class="text-xs font-medium">Academia</span></a>
            <a href="dashboard.php?module=shortener" class="nav-link flex flex-col items-center gap-2 p-4 rounded-xl bg-white/5 hover:bg-white/10"><i class="bi bi-link-45deg text-2xl text-sj-blue"></i><span class="text-xs font-medium">Links</span></a>
            <?php if ($user['membership_type'] === 'ADMINISTRADOR'): ?>
            <a href="admin.php" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-sj-orange/10 text-sj-orange"><i class="bi bi-shield-lock-fill text-2xl"></i><span class="text-xs font-bold">Admin</span></a>
            <?php endif; ?>
            <a href="logout.php" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-red-500/10 text-red-400"><i class="bi bi-power text-2xl"></i><span class="text-xs font-bold">Salir</span></a>
            <a href="https://t.me/surveyjuniorus" target="_blank" class="col-span-3 flex items-center justify-center gap-2 p-3 rounded-xl bg-gradient-to-r from-orange-600 to-red-600 font-bold mt-2"><i class="bi bi-fire"></i> Contactar Soporte</a>
        </div>
    </div>

    <div id="app-templates" style="display: none;">
        <template id="skeleton-generator-page">
            <div class="animate-pulse space-y-4">
                <div class="h-32 bg-white/5 rounded-2xl"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div class="h-64 bg-white/5 rounded-2xl"></div><div class="h-64 bg-white/5 rounded-2xl"></div></div>
            </div>
        </template>
    </div>
    
    <div class="toast-container position-fixed bottom-4 right-4 p-3" id="toast-container" style="z-index: 1100;"></div>
    <?php include 'includes/modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="dashboard-script.js?v=42.0"></script> 
</body>
</html>