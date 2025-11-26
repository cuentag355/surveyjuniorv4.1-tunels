<?php
// modules/home.php (v5.2 - Logos XL Full Cover)
if (!isset($view_data)) return;
$user = $view_data['user'];
$can_use_generators = $view_data['can_use_generators'];
$logo_path = "/img/panel-logos/";
$cache_version = "?v=3.0"; // Forzar recarga de imágenes nuevas
?>

<style>
    /* Grid Principal */
    .generator-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1.5rem;
    }

    /* --- ESTILOS DE LOGOS --- */

    /* 1. Logo Normal (Tarjetas Pequeñas) - Se mantiene contenido y limpio */
    .gen-card-logo {
        height: 50px;
        width: auto;
        max-width: 80%;
        object-fit: contain;
        position: relative;
        z-index: 2;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
    }

    /* 2. Logo XL (Tarjetas Grandes) - MODO FULL COVER */
    .gen-card-logo-xl {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Llena todo el espacio sin deformarse (recorta si sobra) */
        object-position: center;
        position: absolute; /* Se pega a los bordes del contenedor padre */
        top: 0; left: 0;
        z-index: 0;
        opacity: 0.9; /* Sutil transparencia para mezclar con el fondo si es PNG transparente */
        transition: transform 0.5s ease;
    }

    /* Efecto Zoom suave al pasar el mouse */
    .gen-card:hover .gen-card-logo-xl {
        transform: scale(1.05);
    }

    /* --- LAYOUT RESPONSIVO (Cuadrícula) --- */
    
    /* Tarjetas Grandes (2 columnas en PC) */
    .gen-card-large { grid-column: span 12; }
    @media (min-width: 992px) { .gen-card-large { grid-column: span 6; } }

    /* Tarjetas Pequeñas (4 columnas en PC) */
    .gen-card-small { grid-column: span 12; }
    @media (min-width: 576px) { .gen-card-small { grid-column: span 6; } }
    @media (min-width: 1200px) { .gen-card-small { grid-column: span 3; } }
</style>

<div class="animate-fade-in">
    
    <h1 class="font-display text-4xl font-bold text-white mb-2">Hola, <span class="text-transparent bg-clip-text bg-gradient-to-r from-sj-green to-sj-blue"><?= htmlspecialchars($user['username']) ?></span></h1>
    <p class="text-gray-400 text-lg mb-8">Optimiza tu flujo de trabajo.</p>

    <!-- Stats (Diseño Aurora) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10"><i class="bi bi-rocket-launch-fill text-6xl text-sj-green"></i></div>
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Generados</div>
            <div class="text-3xl font-display font-bold text-white" id="stat-total-jumpers-all-time">...</div>
            <div class="text-sj-green text-sm" id="stat-jumpers-month">...</div>
        </div>
        <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10"><i class="bi bi-trophy-fill text-6xl text-sj-yellow"></i></div>
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Rango</div>
            <div class="text-3xl font-display font-bold text-white" id="stat-rank-name">...</div>
            <div class="text-yellow-400 text-sm" id="stat-rank-level">...</div>
        </div>
        <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10"><i class="bi bi-database-fill-add text-6xl text-sj-blue"></i></div>
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Aportes</div>
            <div class="text-3xl font-display font-bold text-white" id="stat-total-subids">...</div>
            <div class="text-blue-400 text-sm" id="stat-subids-rank">...</div>
        </div>
    </div>

    <?php if (!$can_use_generators): ?>
        <div class="glass-panel border-l-4 border-red-500 p-6 rounded-r-xl mb-8 flex items-center gap-4">
            <div class="bg-red-500/20 p-3 rounded-full"><i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i></div>
            <div>
                <h3 class="text-white font-bold text-lg">Membresía Vencida</h3>
                <p class="text-gray-400">Renueva tu plan para acceder.</p>
            </div>
            <a href="dashboard.php?module=membership" class="ml-auto px-6 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg font-bold transition-colors">Renovar</a>
        </div>
    <?php else: ?>

        <!-- SECCIÓN 1: PRINCIPALES -->
        <h2 class="font-display text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="bi bi-star-fill text-sj-yellow"></i> Principales
        </h2>
        
        <div class="generator-grid mb-12">
            
            <!-- Opensurvey (Logo Full Cover) -->
            <a href="dashboard.php?module=opensurvey" class="nav-link gen-card-large glass-panel rounded-2xl p-0 group hover:scale-[1.01] transition-all duration-300 overflow-hidden border border-white/5 hover:border-sj-green/50">
                <!-- Encabezado Alto (180px) -->
                <div class="h-[180px] bg-gradient-to-br from-[#E63B2E] to-[#96261d] relative w-full overflow-hidden flex items-center justify-center">
                    <!-- Imagen en modo COVER absoluto -->
                    <img src="<?= $logo_path ?>logo-os.png<?= $cache_version ?>" class="gen-card-logo-xl" onerror="this.style.display='none'">
                    
                    <!-- Texto Fallback por si la imagen falla -->
                    <span class="relative z-10 text-white font-bold text-2xl drop-shadow-md" style="display:none">Opensurvey</span>
                </div>
                <div class="p-6 bg-sj-card/50 backdrop-blur-xl relative z-10">
                    <h3 class="text-2xl font-display font-bold text-white mb-1">Opensurvey</h3>
                    <p class="text-gray-400 text-sm">Generador automático. Detecta parámetros al instante.</p>
                </div>
            </a>

            <!-- Meinungsplatz (Logo Full Cover) -->
            <a href="dashboard.php?module=meinungsplatz" class="nav-link gen-card-large glass-panel rounded-2xl p-0 group hover:scale-[1.01] transition-all duration-300 overflow-hidden border border-white/5 hover:border-blue-500/50">
                <!-- Encabezado Alto (180px) -->
                <div class="h-[180px] bg-gradient-to-br from-[#00466A] to-[#00283d] relative w-full overflow-hidden flex items-center justify-center">
                    <!-- Imagen en modo COVER absoluto -->
                    <img src="<?= $logo_path ?>logo-mp.png<?= $cache_version ?>" class="gen-card-logo-xl" onerror="this.style.display='none'">
                </div>
                <div class="p-6 bg-sj-card/50 backdrop-blur-xl relative z-10">
                    <h3 class="text-2xl font-display font-bold text-white mb-1">Meinungsplatz</h3>
                    <p class="text-gray-400 text-sm">Conexión directa a la base de datos colaborativa.</p>
                </div>
            </a>

        </div>

        <!-- SECCIÓN 2: PANELES (Secundarios) -->
        <h2 class="font-display text-xl font-bold text-gray-300 mb-6">Otros Paneles</h2>
        <div class="generator-grid mb-12">
            <?php 
            $minis = [
                ['id'=>'samplicio', 'name'=>'Samplicio', 'desc'=>'Generador inteligente.', 'img'=>'logo-samplicio.png', 'grad'=>'from-indigo-900 to-purple-900'],
                ['id'=>'dkr', 'name'=>'DKR / SSI', 'desc'=>'Dynata System.', 'img'=>'logo-dynata.png', 'grad'=>'from-sky-900 to-blue-900'],
                ['id'=>'spectrum', 'name'=>'Spectrum', 'desc'=>'En construcción.', 'img'=>'logo-spectrum.png', 'grad'=>'from-yellow-900 to-orange-900'],
                ['id'=>'cint', 'name'=>'CINT', 'desc'=>'Collector API.', 'img'=>'logo-cint.png', 'grad'=>'from-purple-900 to-pink-900'],
            ];
            foreach($minis as $m): ?>
            <a href="dashboard.php?module=<?= $m['id'] ?>" class="nav-link gen-card-small glass-panel rounded-2xl p-0 group hover:-translate-y-1 transition-transform duration-300 overflow-hidden">
                <div class="h-28 bg-gradient-to-br <?= $m['grad'] ?> flex items-center justify-center relative">
                    <img src="<?= $logo_path . $m['img'] . $cache_version ?>" class="gen-card-logo group-hover:scale-110 transition-transform" onerror="this.style.display='none'">
                </div>
                <div class="p-4 bg-sj-card">
                    <h4 class="font-bold text-white text-lg"><?= $m['name'] ?></h4>
                    <p class="text-xs text-gray-500"><?= $m['desc'] ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- SECCIÓN 3: INTERNOS -->
        <h2 class="font-display text-xl font-bold text-gray-300 mb-6">Módulos Internos</h2>
        <div class="generator-grid mb-8">
             <?php 
            $internos = [
                ['id'=>'horizoom', 'name'=>'Horizoom', 'desc'=>'Routing system.', 'img'=>'logo-horizoom.png', 'grad'=>'from-violet-900 to-fuchsia-900'],
                ['id'=>'marketmind', 'name'=>'MarketMind', 'desc'=>'Study extractor.', 'img'=>'logo-marketmind.png', 'grad'=>'from-emerald-900 to-green-900'],
                ['id'=>'opinionexchange', 'name'=>'OpinionEx', 'desc'=>'UserID parser.', 'img'=>'logo-oe.png', 'grad'=>'from-orange-900 to-red-900'],
                ['id'=>'m3global', 'name'=>'M3 Global', 'desc'=>'Medical panel.', 'img'=>'logo-m3.png', 'grad'=>'from-blue-950 to-blue-900'],
            ];
            foreach($internos as $m): ?>
            <a href="dashboard.php?module=<?= $m['id'] ?>" class="nav-link gen-card-small glass-panel rounded-2xl p-0 group hover:-translate-y-1 transition-transform duration-300 overflow-hidden">
                <div class="h-28 bg-gradient-to-br <?= $m['grad'] ?> flex items-center justify-center relative">
                    <img src="<?= $logo_path . $m['img'] . $cache_version ?>" class="gen-card-logo group-hover:scale-110 transition-transform" onerror="this.style.display='none'">
                </div>
                <div class="p-4 bg-sj-card">
                    <h4 class="font-bold text-white text-lg"><?= $m['name'] ?></h4>
                    <p class="text-xs text-gray-500"><?= $m['desc'] ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Footer Stats -->
    <div class="glass-panel rounded-xl p-4 flex flex-wrap gap-6 text-sm text-gray-500 font-mono">
        <div><span class="text-gray-600">DE:</span> <span id="stat-country-de" class="text-gray-300 font-bold">...</span></div>
        <div><span class="text-gray-600">AT:</span> <span id="stat-country-at" class="text-gray-300 font-bold">...</span></div>
        <div><span class="text-gray-600">CH:</span> <span id="stat-country-ch" class="text-gray-300 font-bold">...</span></div>
    </div>
</div>