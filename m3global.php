<?php
// modules/m3global.php (Quantum Design)
if (!isset($view_data)) return;
$can_use_generators = $view_data['can_use_generators'];
$logo_path = "/img/panel-logos/";
?>

<div class="max-w-4xl mx-auto animate-fade-in">
    <div class="glass-panel rounded-2xl p-8 mb-8 flex flex-col md:flex-row items-center gap-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-800/20 to-indigo-800/10 z-0"></div>
        <div class="relative z-10 w-24 h-24 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shadow-xl backdrop-blur-sm shrink-0">
            <img src="<?= $logo_path ?>logo-m3.png?v=2.0" class="w-16 h-16 object-contain drop-shadow-lg" onerror="this.style.display='none'">
        </div>
        <div class="relative z-10 text-center md:text-left">
            <h1 class="font-display text-3xl font-bold text-white mb-2">M3 Global</h1>
            <p class="text-gray-400">Panel Médico. Extracción precisa de <code class="text-blue-400">survey_key</code> y <code class="text-blue-400">user_id</code>.</p>
        </div>
    </div>

    <div class="glass-panel rounded-2xl p-8 border-t-4 border-blue-600">
        <?php if (!$can_use_generators): ?>
            <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 text-center text-red-200">Membresía vencida.</div>
        <?php else: ?>
            <form id="m3global-generator-form" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">URL de M3 Global</label>
                    <textarea id="gen-urls-m3" name="urls" rows="4" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all resize-none custom-scrollbar" required></textarea>
                </div>
                <button type="submit" id="m3-gen-submit-btn" class="w-full group relative flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-600 hover:to-indigo-600 transition-all shadow-lg shadow-blue-600/20">
                    <span class="btn-text">Generar Jumper</span>
                    <span class="spinner-border w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin hidden ml-2"></span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>