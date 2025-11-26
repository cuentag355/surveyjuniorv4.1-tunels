<?php
// modules/academia.php (Quantum Design)
if (!isset($view_data)) return;
$user = $view_data['user'];
$pdo = $view_data['pdo'];
$academy_is_disabled = file_exists(__DIR__ . '/../ACADEMIA_DISABLED');

// Validar Acceso
if ($academy_is_disabled || ($user['banned'] == 0 && $user['membership_type'] !== 'ADMINISTRADOR' && $user['membership_type'] !== 'PRO')) {
     echo "<div class='glass-panel rounded-2xl p-10 text-center border-l-4 border-orange-500'>
          <i class='bi bi-lock-fill text-4xl text-orange-500 mb-4 block'></i>
          <h2 class='text-2xl font-bold text-white mb-2'>Acceso Restringido</h2>
          <p class='text-gray-400'>Este contenido es exclusivo para miembros PRO. <a href='dashboard.php?module=membership' class='text-orange-400 underline'>Mejorar plan</a>.</p>
      </div>";
    return;
}

// Cargar Datos
$modulos = [];
try {
    $stmt_modulos = $pdo->query("SELECT * FROM academia_modulos ORDER BY orden ASC, id ASC");
    $modulos_data = $stmt_modulos->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cursos = $pdo->query("SELECT * FROM academia_cursos ORDER BY modulo_id ASC, orden ASC, id ASC");
    $cursos_data = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modulos_data as $modulo) {
        $modulos[$modulo['id']] = $modulo;
        $modulos[$modulo['id']]['cursos'] = [];
    }
    foreach ($cursos_data as $curso) {
        if (isset($modulos[$curso['modulo_id']])) {
            $modulos[$curso['modulo_id']]['cursos'][] = $curso;
        }
    }
} catch (PDOException $e) { return; }
?>

<div class="max-w-7xl mx-auto animate-fade-in">

    <div class="mb-10 text-center md:text-left">
        <h1 class="font-display text-4xl font-bold text-white mb-2 flex items-center gap-3">
            <span class="p-2 bg-sj-purple/20 rounded-lg text-sj-purple"><i class="bi bi-mortarboard-fill"></i></span>
            Academia
        </h1>
        <p class="text-gray-400 text-lg">Domina el arte de las encuestas con nuestras guías exclusivas.</p>
    </div>

    <?php if (empty($modulos)): ?>
        <div class="glass-panel p-10 rounded-2xl text-center">
            <p class="text-gray-500">No hay contenido disponible aún.</p>
        </div>
    <?php else: ?>
        
        <div class="space-y-16">
            <?php foreach ($modulos as $modulo): ?>
                <?php if (empty($modulo['cursos'])) continue; ?>
                
                <section>
                    <div class="flex items-end gap-4 mb-6 border-b border-white/10 pb-4">
                        <h2 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($modulo['titulo']) ?></h2>
                        <?php if ($modulo['descripcion']): ?>
                            <span class="text-gray-500 text-sm mb-1 hidden md:inline-block"><?= htmlspecialchars($modulo['descripcion']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($modulo['cursos'] as $curso): 
                            // Icono y color según tipo
                            $typeIcon = match($curso['tipo']) {
                                'video' => 'bi-play-circle-fill',
                                'pdf' => 'bi-file-earmark-pdf-fill',
                                default => 'bi-file-text-fill'
                            };
                            $typeColor = match($curso['tipo']) {
                                'video' => 'text-red-400',
                                'pdf' => 'text-orange-400',
                                default => 'text-blue-400'
                            };
                            $grad = match($curso['tipo']) {
                                'video' => 'from-red-900/40 to-purple-900/40',
                                'pdf' => 'from-orange-900/40 to-red-900/40',
                                default => 'from-blue-900/40 to-cyan-900/40'
                            };
                        ?>
                            <div class="group glass-panel rounded-2xl overflow-hidden hover:border-white/20 transition-all duration-300 hover:-translate-y-1 flex flex-col h-full">
                                <div class="h-40 bg-gradient-to-br <?= $grad ?> relative flex items-center justify-center group-hover:brightness-110 transition-all">
                                    <i class="bi <?= $typeIcon ?> text-5xl text-white/80 drop-shadow-lg group-hover:scale-110 transition-transform duration-300"></i>
                                    <div class="absolute bottom-2 right-2 bg-black/60 backdrop-blur px-2 py-1 rounded text-xs font-bold text-white flex items-center gap-1">
                                        <i class="bi bi-clock"></i> <?= $curso['duracion_min'] ?> min
                                    </div>
                                </div>
                                
                                <div class="p-5 flex-1 flex flex-col">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wider <?= $typeColor ?>"><?= $curso['tipo'] ?></span>
                                    </div>
                                    <h3 class="text-lg font-bold text-white mb-2 leading-tight"><?= htmlspecialchars($curso['titulo']) ?></h3>
                                    <p class="text-gray-400 text-sm line-clamp-2 mb-4 flex-1"><?= htmlspecialchars($curso['descripcion']) ?></p>
                                    
                                    <button class="view-media-btn w-full py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white font-medium border border-white/10 transition-colors flex items-center justify-center gap-2"
                                            data-tipo="<?= $curso['tipo'] ?>" 
                                            data-url="<?= htmlspecialchars($curso['url_contenido']) ?>" 
                                            data-titulo="<?= htmlspecialchars($curso['titulo']) ?>">
                                        <i class="bi bi-eye"></i> Ver Lección
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>