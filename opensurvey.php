<?php
// modules/opensurvey.php (v15.0 - Hub Híbrido: Clásico vs Túnel)
if (!isset($view_data)) return;
$can_use_generators = $view_data['can_use_generators'];
$logo_path = "/img/panel-logos/";
?>

<div class="max-w-6xl mx-auto animate-fade-in">

    <div id="os-selection-screen">
        <div class="text-center mb-10">
            <img src="<?= $logo_path ?>logo-os.png?v=2.0" class="w-20 h-20 object-contain mx-auto mb-4 drop-shadow-lg">
            <h1 class="font-display text-3xl font-bold text-white">Selecciona tu Protocolo</h1>
            <p class="text-gray-400">Elige el nivel de seguridad para esta encuesta.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            
            <div class="glass-panel p-8 rounded-2xl border border-white/5 hover:border-red-500/50 transition-all cursor-pointer group relative overflow-hidden" id="btn-select-classic">
                <div class="absolute inset-0 bg-gradient-to-br from-red-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-red-500/20 flex items-center justify-center mb-4 text-red-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Vieja Seguridad</h3>
                    <p class="text-gray-400 text-sm mb-4">Generador clásico instantáneo. Extrae parámetros y crea el link final al momento.</p>
                    <div class="flex gap-2">
                        <span class="text-xs bg-white/5 px-2 py-1 rounded text-gray-500">Rápido</span>
                        <span class="text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded">Riesgo Alto</span>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-8 rounded-2xl border border-white/5 hover:border-green-500/50 transition-all cursor-pointer group relative overflow-hidden" id="btn-select-tunnel">
                <div class="absolute inset-0 bg-gradient-to-br from-green-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center mb-4 text-green-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Nueva Seguridad</h3>
                    <p class="text-gray-400 text-sm mb-4">Túnel V4.0. Simulación humana, cookies, LOI variable y protección anti-ban.</p>
                    <div class="flex gap-2">
                        <span class="text-xs bg-white/5 px-2 py-1 rounded text-gray-500">Lento (10m+)</span>
                        <span class="text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded">Infalible</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="os-classic-wrapper" class="hidden max-w-3xl mx-auto">
        <button class="btn-back mb-6 text-gray-400 hover:text-white flex items-center gap-2 transition-colors"><i class="bi bi-arrow-left"></i> Volver</button>
        
        <div class="glass-panel rounded-2xl p-8 border-t-4 border-red-500">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2"><i class="bi bi-lightning-charge-fill text-red-500"></i> Generador Clásico</h2>
            
            <form id="opensurvey-classic-form" class="space-y-6">
                <input type="hidden" name="mode" value="classic">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Pega tu URL de Opensurvey</label>
                    <textarea name="urls" rows="4" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all resize-none custom-scrollbar" placeholder="https://www.opensurvey.com/survey/..." required></textarea>
                </div>
                <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/20 flex justify-center items-center gap-2">
                    <span class="btn-text">Generar Jumper</span>
                    <span class="spinner-border w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin hidden absolute"></span>
                </button>
            </form>
        </div>
    </div>

    <div id="os-tunnel-wrapper" class="hidden">
        <button class="btn-back mb-6 text-gray-400 hover:text-white flex items-center gap-2 transition-colors"><i class="bi bi-arrow-left"></i> Volver</button>

        <?php if (!$can_use_generators): ?>
            <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 text-center text-red-200">Membresía vencida.</div>
        <?php else: ?>

            <div id="os-phase-input" class="glass-panel rounded-2xl p-8 border-t-4 border-green-500 transition-all">
                <form id="opensurvey-tunnel-form" class="space-y-6">
                    <input type="hidden" name="mode" value="tunnel">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">1. Link de Invitación (integration...)</label>
                        <textarea name="start_url" id="os-input-url" rows="2" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-green-500 focus:ring-1 focus:ring-green-500 transition-all resize-none custom-scrollbar font-mono text-xs" placeholder="https://integration.talkonlinepanel.com/api/public/..." required></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">2. Tiempo de Espera (Minutos)</label>
                        <div class="flex items-center gap-4">
                            <div class="relative w-32">
                                <input type="number" name="custom_minutes" value="10" min="2" max="60" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl py-3 pl-4 pr-2 text-white focus:border-green-500 focus:ring-1 focus:ring-green-500 text-center font-bold text-lg">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs font-bold">MIN</span>
                            </div>
                            <p class="text-xs text-gray-500 flex-1"><i class="bi bi-info-circle"></i> Ajusta según LOI de la encuesta. Se añadirá tiempo aleatorio.</p>
                        </div>
                    </div>

                    <div class="h-px bg-white/5 my-4"></div>

                    <button type="submit" class="w-full group relative flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-green-600 hover:bg-green-500 transition-all shadow-lg shadow-green-600/20">
                        <span class="btn-text flex items-center gap-2"><i class="bi bi-door-open-fill"></i> Iniciar Túnel V4.0</span>
                        <span class="spinner-border w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin hidden absolute"></span>
                    </button>
                </form>
            </div>

            <div id="os-phase-tunnel" class="hidden space-y-4">
                <div class="glass-panel p-4 rounded-xl border border-white/10 bg-black/40 flex justify-between items-center">
                    <div class="text-sm text-gray-300">
                        <strong class="text-white">PASO 1:</strong> Resuelve Captcha / Preguntas.<br>
                        <strong class="text-white">PASO 2:</strong> Al ver la encuesta final (o error), pulsa el botón verde.
                    </div>
                    <button id="os-btn-start-timer" class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-[0_0_20px_rgba(34,197,94,0.3)] animate-pulse">
                        <i class="bi bi-stopwatch"></i> Ya cargó -> Iniciar Espera
                    </button>
                </div>
                <div class="relative w-full h-[600px] bg-white rounded-xl overflow-hidden border-2 border-white/20">
                    <iframe id="os-iframe" src="" class="w-full h-full" referrerpolicy="no-referrer"></iframe>
                    <div id="os-iframe-loader" class="absolute inset-0 bg-sj-dark flex items-center justify-center text-white">
                        <div class="text-center"><div class="spinner-border text-green-500 mb-3" role="status"></div><div>Conectando Túnel...</div></div>
                    </div>
                </div>
            </div>

            <div id="os-phase-timer" class="hidden">
                <div class="glass-panel rounded-2xl p-10 text-center border-t-4 border-yellow-500 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-white/10"><div id="os-progress-bar" class="h-full bg-yellow-500 transition-all duration-1000 ease-linear" style="width: 0%"></div></div>
                    <div class="mb-6"><i class="bi bi-hourglass-split text-6xl text-yellow-500 animate-pulse"></i></div>
                    <h2 class="text-3xl font-bold text-white mb-2">Simulando Actividad Humana</h2>
                    <p class="text-gray-400 mb-8">Esperando tiempo seguro...</p>
                    <div class="text-5xl font-mono font-bold text-white mb-8 tracking-widest" id="os-countdown">00:00</div>
                    <div class="p-4 bg-white/5 rounded-xl border border-white/10 max-w-md mx-auto text-left text-sm text-gray-400">
                        <p class="mb-2"><i class="bi bi-check-circle text-green-500"></i> Cookies: <strong>Preservadas</strong></p>
                        <p><i class="bi bi-circle text-yellow-500" id="os-status-text"></i> Estado: <strong>En progreso...</strong></p>
                    </div>
                    <div id="os-result-zone" class="mt-6 w-full"></div>
                    <button id="os-btn-force-jump" class="mt-8 text-xs text-red-400 hover:text-red-300 underline">(Dev) Forzar salto</button>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>