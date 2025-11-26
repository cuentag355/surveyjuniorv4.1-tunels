<?php
// modules/meinungsplatz.php (v20.0 - Hub Híbrido: Clásico vs Túnel)
if (!isset($view_data)) return;
$can_use_generators = $view_data['can_use_generators'];
$logo_path = "/img/panel-logos/";
?>

<div class="max-w-6xl mx-auto animate-fade-in">

    <div id="mp-selection-screen">
        <div class="text-center mb-10">
            <img src="<?= $logo_path ?>logo-mp.png?v=2.0" class="w-20 h-20 object-contain mx-auto mb-4 drop-shadow-lg">
            <h1 class="font-display text-3xl font-bold text-white">Meinungsplatz <span class="text-sj-blue">Quantum</span></h1>
            <p class="text-gray-400">Base de Datos Colaborativa + Emulación de Comportamiento.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            <div class="glass-panel p-8 rounded-2xl border border-white/5 hover:border-blue-500/50 transition-all cursor-pointer group relative overflow-hidden" id="btn-select-mp-classic">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-blue-500/20 flex items-center justify-center mb-4 text-blue-500 text-2xl group-hover:scale-110 transition-transform"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h3 class="text-xl font-bold text-white mb-2">Generador Clásico</h3>
                    <p class="text-gray-400 text-sm mb-4">Pega texto sucio, extrae IDs y consulta la DB al instante.</p>
                    <span class="text-xs bg-white/5 px-2 py-1 rounded text-gray-500">Rápido</span>
                </div>
            </div>

            <div class="glass-panel p-8 rounded-2xl border border-white/5 hover:border-cyan-500/50 transition-all cursor-pointer group relative overflow-hidden" id="btn-select-mp-tunnel">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-cyan-500/20 flex items-center justify-center mb-4 text-cyan-500 text-2xl group-hover:scale-110 transition-transform"><i class="bi bi-shield-lock-fill"></i></div>
                    <h3 class="text-xl font-bold text-white mb-2">Túnel de Caza V2.0</h3>
                    <p class="text-gray-400 text-sm mb-4">Carga Iframe, captura UserID con Extensión y aplica Jumper con espera.</p>
                    <span class="text-xs bg-cyan-500/20 text-cyan-400 px-2 py-1 rounded">Máxima Seguridad</span>
                </div>
            </div>
        </div>
    </div>

    <div id="mp-classic-wrapper" class="hidden max-w-3xl mx-auto">
        <button class="btn-back mb-6 text-gray-400 hover:text-white flex items-center gap-2 transition-colors"><i class="bi bi-arrow-left"></i> Volver</button>
        <div class="glass-panel rounded-2xl p-8 border-t-4 border-blue-500">
            <form id="meinungsplatz-generator-form" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Pegar Texto / Links</label>
                    <textarea id="gen-urls-mp" name="urls" rows="4" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all resize-none custom-scrollbar" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Projektnummer (Opcional)</label>
                    <input type="text" name="projektnummer" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-3 text-white focus:border-blue-500" placeholder="Si el sistema no lo detecta...">
                </div>
                <button type="submit" id="mp-gen-submit-btn" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-600/20">Generar</button>
            </form>
        </div>
    </div>

    <div id="mp-tunnel-wrapper" class="hidden">
        <button class="btn-back mb-6 text-gray-400 hover:text-white flex items-center gap-2 transition-colors"><i class="bi bi-arrow-left"></i> Volver</button>

        <?php if (!$can_use_generators): ?>
            <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 text-center text-red-200">Membresía vencida.</div>
        <?php else: ?>

            <div id="mp-phase-input" class="glass-panel rounded-2xl p-8 border-t-4 border-cyan-500 transition-all">
                <form id="mp-tunnel-form" class="space-y-6">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-300 mb-2">1. Link de Invitación (m3click...)</label>
                            <input type="text" name="start_url" id="mp-input-url" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all font-mono text-xs" placeholder="https://meinungsplatz.de/m3click?..." required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">2. Projektnummer</label>
                            <input type="text" name="projektnummer" id="mp-input-projekt" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-cyan-500 font-bold text-center tracking-wider" placeholder="Ej: 123456" required pattern="\d+">
                            <p class="text-xs text-gray-500 mt-1">Vital para buscar el SubID.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">3. Tiempo (Min)</label>
                            <div class="relative">
                                <input type="number" name="custom_minutes" value="15" min="2" max="60" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-cyan-500 font-bold text-center">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500">MIN</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-cyan-600/20 flex justify-center items-center gap-2">
                        <span class="btn-text"><i class="bi bi-radar"></i> Validar SubID e Iniciar</span>
                        <span class="spinner-border w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin hidden absolute"></span>
                    </button>
                </form>
            </div>

            <div id="mp-phase-tunnel" class="hidden space-y-4">
                <div class="glass-panel p-4 rounded-xl border border-white/10 bg-black/40 flex justify-between items-center">
                    <div class="text-sm text-gray-300" id="mp-status-bar">
                        <span class="text-yellow-400"><i class="bi bi-search"></i> Esperando captura de UserID...</span>
                    </div>
                    <button id="mp-btn-start-timer" class="px-6 py-3 bg-gray-600 text-gray-400 font-bold rounded-xl cursor-not-allowed" disabled>
                        <i class="bi bi-lock-fill"></i> Esperando ID...
                    </button>
                </div>
                <div class="relative w-full h-[600px] bg-white rounded-xl overflow-hidden border-2 border-white/20">
                    <iframe id="mp-iframe" src="" class="w-full h-full" sandbox="allow-same-origin allow-scripts allow-forms allow-popups" referrerpolicy="no-referrer"></iframe>
                </div>
            </div>

            <div id="mp-phase-timer" class="hidden">
                <div class="glass-panel rounded-2xl p-10 text-center border-t-4 border-yellow-500 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-white/10"><div id="mp-progress-bar" class="h-full bg-yellow-500 transition-all duration-1000 ease-linear" style="width: 0%"></div></div>
                    <div class="text-5xl font-mono font-bold text-white mb-8 tracking-widest" id="mp-countdown">00:00</div>
                    <div id="mp-result-zone" class="mt-6 w-full"></div>
                    <button id="mp-btn-force-jump" class="mt-8 text-xs text-red-400 hover:text-red-300 underline">(Dev) Forzar salto</button>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>