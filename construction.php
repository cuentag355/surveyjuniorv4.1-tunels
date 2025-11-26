<?php
// modules/construction.php (Quantum Design)
if (!isset($view_data)) return;
$module_name = htmlspecialchars($_GET['module'] ?? 'Módulo', ENT_QUOTES, 'UTF-8');
?>

<div class="max-w-2xl mx-auto mt-10 animate-fade-in">
    <div class="glass-panel rounded-3xl p-10 text-center border border-white/10 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-sj-yellow to-orange-500"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-sj-yellow/5 to-transparent pointer-events-none"></div>

        <div class="w-24 h-24 bg-sj-yellow/10 rounded-full flex items-center justify-center mx-auto mb-6 ring-1 ring-sj-yellow/30">
            <i class="bi bi-cone-striped text-4xl text-sj-yellow animate-pulse"></i>
        </div>

        <h1 class="font-display text-3xl font-bold text-white mb-3">En Construcción</h1>
        <p class="text-gray-400 text-lg mb-8">
            El módulo <span class="text-white font-bold bg-white/10 px-2 py-0.5 rounded"><?= ucwords($module_name) ?></span> se está forjando en el laboratorio.
        </p>

        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-gray-400">
            <div class="w-2 h-2 bg-sj-yellow rounded-full animate-ping"></div>
            Desarrollo en progreso...
        </div>
    </div>
</div>