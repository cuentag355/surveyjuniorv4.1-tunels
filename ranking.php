<?php
// modules/ranking.php (Quantum Design Container)
if (!isset($view_data)) return;
?>

<div class="max-w-5xl mx-auto animate-fade-in">
    <div class="text-center mb-10">
        <h1 class="font-display text-4xl font-bold text-white mb-2">Salón de la Fama</h1>
        <p class="text-gray-400">Los colaboradores más legendarios de nuestra comunidad.</p>
    </div>

    <div id="ranking-podium-container">
        <div class="flex justify-center items-end gap-4 mb-12">
            <div class="w-1/3 max-w-[200px] h-48 bg-white/5 rounded-t-2xl animate-pulse relative"></div>
            <div class="w-1/3 max-w-[200px] h-64 bg-white/10 rounded-t-2xl animate-pulse relative"></div>
            <div class="w-1/3 max-w-[200px] h-40 bg-white/5 rounded-t-2xl animate-pulse relative"></div>
        </div>
        <div class="space-y-3">
            <div class="h-16 w-full bg-white/5 rounded-xl animate-pulse"></div>
            <div class="h-16 w-full bg-white/5 rounded-xl animate-pulse"></div>
            <div class="h-16 w-full bg-white/5 rounded-xl animate-pulse"></div>
        </div>
    </div>
</div>