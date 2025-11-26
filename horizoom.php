<?php
// modules/horizoom.php (Quantum Design)
if (!isset($view_data)) return;
$can_use_generators = $view_data['can_use_generators'];
$logo_path = "/img/panel-logos/";
?>

<div class="max-w-4xl mx-auto animate-fade-in">
    <div class="glass-panel rounded-2xl p-8 mb-8 flex flex-col md:flex-row items-center gap-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-violet-600/20 to-fuchsia-600/10 z-0"></div>
        <div class="relative z-10 w-24 h-24 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shadow-xl backdrop-blur-sm shrink-0">
            <img src="<?= $logo_path ?>logo-horizoom.png?v=2.0" class="w-16 h-16 object-contain drop-shadow-lg" onerror="this.style.display='none'">
        </div>
        <div class="relative z-10 text-center md:text-left">
            <h1 class="font-display text-3xl font-bold text-white mb-2">Horizoom</h1>
            <p class="text-gray-400">Enrutador inteligente. Busca <code class="text-sj-purple">i_survey</code> o <code class="text-sj-purple">a</code> en enlaces complejos.</p>
        </div>
    </div>

    <div class="glass-panel rounded-2xl p-8 border-t-4 border-sj-purple">
        <?php if (!$can_use_generators): ?>
            <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 text-center text-red-200">Membresía vencida.</div>
        <?php else: ?>
            <form id="horizoom-generator-form" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">URL de Horizoom</label>
                    <textarea id="gen-urls-hz" name="urls" rows="4" class="w-full bg-sj-dark/50 border border-white/10 rounded-xl p-4 text-white focus:border-sj-purple focus:ring-1 focus:ring-sj-purple transition-all resize-none custom-scrollbar" required></textarea>
                </div>
                <button type="submit" id="hz-gen-submit-btn" class="w-full group relative flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 transition-all shadow-lg shadow-violet-600/20">
                    <span class="btn-text">Generar Jumper</span>
                    <span class="spinner-border w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin hidden ml-2"></span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>