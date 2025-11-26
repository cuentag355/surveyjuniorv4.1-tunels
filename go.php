<?php
// go.php (v11.0 - Stealth Redirect)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';
require 'functions.php';

$slug = $_GET['slug'] ?? null;
$target_url = null;
$error = false;

// 1. Validación y Búsqueda
if (empty($slug) || !preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
    $error = true;
} else {
    try {
        $stmt = $pdo->prepare("SELECT target_url FROM short_links WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $target_url = $stmt->fetchColumn();

        if (!$target_url) {
            $error = true;
            if (function_exists('logActivity')) logActivity($pdo, null, 'SYSTEM_GO', 'Redirect Fallido (404)', "Slug: {$slug}");
        } else {
            if (function_exists('logActivity')) logActivity($pdo, null, 'SYSTEM_GO', 'Redirect DB Exitoso', "Slug: {$slug}");
        }
    } catch (PDOException $e) {
        error_log("Error en go.php: " . $e->getMessage());
        $error = true;
    }
}

// 2. Manejo de Errores
if ($error || !$target_url) {
    header("Location: index.php?error=link_not_found");
    exit;
}

// 3. Cabecera de Referrer Policy (Primera línea de defensa)
header("Referrer-Policy: no-referrer");
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="referrer" content="no-referrer">
    
    <title>Redirigiendo... - SurveyJunior</title>
    
    <meta http-equiv="refresh" content="5;url=<?= htmlspecialchars($target_url, ENT_QUOTES, 'UTF-8') ?>" id="meta-refresh-hidden">
    
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;500;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6' } },
                    animation: { 'blob': 'blob 10s infinite', 'float': 'float 3s ease-in-out infinite', 'rocket-shake': 'rocketShake 0.5s cubic-bezier(.36,.07,.19,.97) both infinite' },
                    keyframes: {
                        blob: { '0%': { transform: 'translate(0px, 0px) scale(1)' }, '33%': { transform: 'translate(30px, -50px) scale(1.1)' }, '66%': { transform: 'translate(-20px, 20px) scale(0.9)' }, '100%': { transform: 'translate(0px, 0px) scale(1)' } },
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-15px)' } },
                        rocketShake: { '10%, 90%': { transform: 'translate3d(-1px, 0, 0)' }, '20%, 80%': { transform: 'translate3d(2px, 0, 0)' }, '30%, 50%, 70%': { transform: 'translate3d(-2px, 0, 0)' }, '40%, 60%': { transform: 'translate3d(2px, 0, 0)' } }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card { background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .progress-bar-bg { background: rgba(255, 255, 255, 0.1); border-radius: 999px; overflow: hidden; height: 6px; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--sj-green, #30E8BF), var(--sj-blue, #3B82F6)); width: 0%; transition: width 0.1s linear; border-radius: 999px; box-shadow: 0 0 10px rgba(48, 232, 191, 0.5); }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased h-screen w-full overflow-hidden flex items-center justify-center relative">

    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-sj-purple/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob"></div>
        <div class="absolute bottom-[-10%] right-[20%] w-[500px] h-[500px] bg-sj-blue/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-40"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-6">
        <div class="glass-card rounded-3xl p-10 w-full text-center relative overflow-hidden border border-white/10">
            
            <div class="mb-8 relative inline-block">
                <div class="absolute inset-0 bg-sj-blue/30 blur-xl rounded-full animate-pulse"></div>
                <i class="bi bi-rocket-takeoff-fill text-6xl text-transparent bg-clip-text bg-gradient-to-tr from-sj-green to-sj-blue animate-float inline-block filter drop-shadow-lg"></i>
            </div>

            <h1 class="font-display text-3xl font-bold text-white mb-2">Iniciando Salto...</h1>
            <p class="text-gray-400 text-sm mb-8">Te estamos redirigiendo a tu destino de forma segura.</p>

            <div class="bg-black/30 rounded-xl p-4 mb-8 border border-white/5 backdrop-blur-sm">
                <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">Atajo</div>
                <div class="font-mono text-sj-green text-lg truncate px-2 tracking-wide">
                    /go/<span class="text-white font-bold"><?= htmlspecialchars($slug) ?></span>
                </div>
            </div>

            <div class="mb-8">
                <div class="flex justify-between text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                    <span>Cargando motores</span>
                    <span id="countdown-text">3s</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-fill"></div>
                </div>
            </div>

            <button onclick="redirectToTarget(true)" class="w-full group relative py-3 px-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold rounded-xl transition-all duration-300 flex justify-center items-center gap-2">
                <span>Saltar Ahora</span>
                <i class="bi bi-box-arrow-up-right text-gray-400 group-hover:text-white transition-colors"></i>
            </button>
        </div>
        <p class="text-center text-xs text-gray-600 mt-8 font-mono">SurveyJunior Quantum Engine</p>
    </div>

    <script>
        const TARGET_URL = '<?= $target_url; ?>'; // PHP injects URL here
        // Reduje el tiempo a 3s para que sea más ágil
        const TOTAL_TIME = 3000; 
        let remainingTime = TOTAL_TIME;
        const intervalStep = 50; 
        
        const countdownText = document.getElementById('countdown-text');
        const progressFill = document.getElementById('progress-fill');
        const rocketIcon = document.querySelector('.bi-rocket-takeoff-fill');

        function redirectToTarget(immediate = false) {
            if (immediate) {
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.5s ease';
            }
            // Usamos window.location.replace para no dejar historial
            window.location.replace(TARGET_URL);
        }

        const timer = setInterval(() => {
            remainingTime -= intervalStep;
            const progress = ((TOTAL_TIME - remainingTime) / TOTAL_TIME) * 100;
            
            if (progressFill) progressFill.style.width = `${Math.min(progress, 100)}%`;
            if (countdownText) countdownText.textContent = `${Math.ceil(remainingTime / 1000)}s`;

            if (remainingTime < 1000 && rocketIcon) {
                rocketIcon.classList.remove('animate-float');
                rocketIcon.style.animation = 'rocketShake 0.1s infinite';
                rocketIcon.style.color = '#FACC15'; 
            }

            if (remainingTime <= 0) {
                clearInterval(timer);
                redirectToTarget();
            }
        }, intervalStep);
        
        const metaRefresh = document.getElementById('meta-refresh-hidden');
        if (metaRefresh) setTimeout(() => metaRefresh.remove(), 4000);
    </script>
</body>
</html>