<?php
// modules/shortener.php (Quantum Design)
if (!isset($view_data)) return;
$user = $view_data['user'];
$pdo = $view_data['pdo'];

$all_links = [];
try {
    $stmt = $pdo->prepare("SELECT id, slug, target_url, created_at FROM short_links ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $all_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
$base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/go/';
?>

<div class="max-w-5xl mx-auto animate-fade-in">
    
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-white mb-2 flex items-center gap-3">
                <i class="bi bi-link-45deg text-sj-green"></i> Links Útiles
            </h1>
            <p class="text-gray-400">Accesos directos creados por la administración.</p>
        </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden border-t-4 border-sj-green">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-white/5 text-gray-400 font-display uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Atajo</th>
                        <th class="px-6 py-4">Destino</th>
                        <th class="px-6 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($all_links)): ?>
                        <tr><td colspan="3" class="px-6 py-12 text-center text-gray-500 italic">No hay enlaces configurados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_links as $link): ?>
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4">
                                <a href="<?= htmlspecialchars($base_url . $link['slug']) ?>" target="_blank" class="flex items-center gap-2 text-sj-green font-bold font-mono hover:underline">
                                    <i class="bi bi-box-arrow-up-right text-xs opacity-50"></i>
                                    /go/<?= htmlspecialchars($link['slug']) ?>
                                </a>
                                <div class="text-xs text-gray-500 mt-1"><?= (new DateTime($link['created_at']))->format('d M, Y') ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-300 max-w-md truncate" title="<?= htmlspecialchars($link['target_url']) ?>">
                                    <?= htmlspecialchars($link['target_url']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="copyJumper('<?= htmlspecialchars($base_url . $link['slug'], ENT_QUOTES) ?>', this)" 
                                        class="px-4 py-2 rounded-lg bg-white/5 hover:bg-sj-green/20 text-gray-300 hover:text-sj-green border border-white/10 transition-all active:scale-95">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>