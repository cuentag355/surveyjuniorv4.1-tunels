<?php
// modules/maintenance.php
?>
<div class="max-w-2xl mx-auto mt-20 animate-fade-in">
    <div class="glass-panel rounded-3xl p-12 text-center border border-white/10 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-500 to-orange-600"></div>
        <div class="absolute inset-0 bg-red-500/5 pointer-events-none"></div>

        <div class="w-24 h-24 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-6 ring-1 ring-red-500/30 shadow-[0_0_30px_rgba(239,68,68,0.2)]">
            <i class="bi bi-cone-striped text-5xl text-red-500"></i>
        </div>

        <h1 class="font-display text-4xl font-bold text-white mb-4">Sistema en Mantenimiento</h1>
        
        <p class="text-gray-400 text-lg mb-8 leading-relaxed">
            Estamos realizando mejoras críticas en la infraestructura <strong>Quantum</strong>. 
            <br>Los módulos están desactivados temporalmente por seguridad.
        </p>

        <div class="flex justify-center gap-4">
            <button onclick="window.location.reload()" class="px-6 py-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-white font-bold transition-all flex items-center gap-2">
                <i class="bi bi-arrow-clockwise"></i> Reintentar
            </button>
            <a href="logout.php" class="px-6 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-red-600/20 flex items-center gap-2">
                <i class="bi bi-box-arrow-left"></i> Salir
            </a>
        </div>
        
        <p class="mt-8 text-xs text-gray-600 font-mono">ERROR_CODE: 503_SERVICE_UNAVAILABLE</p>
    </div>
</div>