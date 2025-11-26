<?php
// modules/membership.php (v4.0 - Fix de Fechas y Errores)
if (!isset($view_data)) return;
$user = $view_data['user'];
$pdo = $view_data['pdo'];

// Cargar datos de pago de forma segura
$payment_settings = [
    'bank_name' => 'Banesco', 
    'phone_number' => '...', 
    'document_number' => '...'
];
if (file_exists(__DIR__ . '/../payment_settings.php')) {
    $loaded_settings = include(__DIR__ . '/../payment_settings.php');
    if (is_array($loaded_settings)) {
        $payment_settings = array_merge($payment_settings, $loaded_settings);
    }
}

// Obtener historial (Manejo de errores silencioso)
$payments = [];
try {
    $stmt = $pdo->prepare("SELECT created_at, reference_number, status, admin_notes FROM payment_proofs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback: array vacío
}

// Configurar estado visual de forma segura
$status_color = 'text-gray-400';
$status_bg = 'from-gray-800 to-gray-900';
$status_icon = 'bi-shield-lock';
$status_text = $user['membership_type'] ?? 'DESCONOCIDO';
$status_date = '';

try {
    switch ($user['membership_type']) {
        case 'ADMINISTRADOR': 
            $status_color = 'text-sj-yellow'; $status_bg = 'from-yellow-900/40 to-orange-900/20'; $status_icon = 'bi-shield-shaded'; $status_date = 'Acceso Ilimitado'; 
            break;
        case 'PRO': 
            $status_color = 'text-sj-green'; $status_bg = 'from-emerald-900/40 to-teal-900/20'; $status_icon = 'bi-patch-check-fill'; 
            // Fix de Fecha: Verificar si existe antes de crear DateTime
            if (!empty($user['membership_expires'])) {
                $status_date = 'Vence: ' . (new DateTime($user['membership_expires']))->format('d/m/Y'); 
            } else {
                $status_date = 'Vence: N/A';
            }
            break;
        case 'PRUEBA GRATIS': 
            $status_color = 'text-blue-400'; $status_bg = 'from-blue-900/40 to-indigo-900/20'; $status_icon = 'bi-hourglass-split'; 
            $limit = (int)($user['jumper_limit'] ?? 5);
            $count = (int)($user['jumper_count'] ?? 0);
            $status_date = "Usos restantes: " . max(0, $limit - $count); 
            break;
        case 'VENCIDO': 
            $status_color = 'text-red-400'; $status_bg = 'from-red-900/40 to-pink-900/20'; $status_icon = 'bi-x-octagon-fill'; $status_date = 'Renueva tu acceso'; 
            break;
        default:
            $status_text = 'Sin Membresía';
            break;
    }
} catch (Exception $e) {
    $status_date = 'Error en datos';
}
?>

<div class="max-w-6xl mx-auto animate-fade-in">

    <!-- Header de Estado -->
    <div class="glass-panel rounded-3xl p-1 overflow-hidden mb-10 relative">
        <div class="absolute inset-0 bg-gradient-to-br <?= $status_bg ?> opacity-50"></div>
        <div class="relative bg-sj-dark/40 backdrop-blur-md rounded-[20px] p-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 rounded-2xl bg-white/5 flex items-center justify-center shadow-inner border border-white/10 text-4xl <?= $status_color ?>">
                    <i class="bi <?= $status_icon ?>"></i>
                </div>
                <div>
                    <h2 class="text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Tu Plan Actual</h2>
                    <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-1"><?= htmlspecialchars($status_text) ?></h1>
                    <p class="<?= $status_color ?> font-medium flex items-center gap-2"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($status_date) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" id="payment-section">
        
        <!-- Columna Izquierda: Métodos de Pago -->
        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-xl font-display font-bold text-white flex items-center gap-2"><i class="bi bi-credit-card-2-front text-sj-blue"></i> Métodos de Pago</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- PayPal -->
                <div class="glass-panel p-6 rounded-2xl border border-white/5 hover:border-blue-500/50 transition-colors group">
                    <div class="flex justify-between items-start mb-4">
                        <i class="bi bi-paypal text-3xl text-[#0070BA]"></i>
                        <span class="bg-blue-500/20 text-blue-300 text-xs font-bold px-2 py-1 rounded uppercase">USD</span>
                    </div>
                    <div class="space-y-1 mb-6">
                        <p class="text-gray-400 text-sm">Envía tu pago a:</p>
                        <p class="text-white font-mono font-bold text-lg tracking-wide group-hover:text-blue-400 transition-colors break-all">admin@surveyjunior.us</p>
                        <p class="text-sj-green font-bold text-xl mt-2">$5.00 <span class="text-sm text-gray-500 font-normal">/ mes</span></p>
                    </div>
                    <button data-bs-toggle="modal" data-bs-target="#uploadProofModal" data-method="PayPal" class="w-full py-2 rounded-lg bg-[#0070BA] hover:bg-[#003087] text-white font-bold transition-colors">Reportar Pago</button>
                </div>

                <!-- Pagomóvil -->
                <div class="glass-panel p-6 rounded-2xl border border-white/5 hover:border-sj-green/50 transition-colors group">
                    <div class="flex justify-between items-start mb-4">
                        <i class="bi bi-phone text-3xl text-sj-green"></i>
                        <span class="bg-sj-green/20 text-sj-green text-xs font-bold px-2 py-1 rounded uppercase">Bs.</span>
                    </div>
                    <div class="space-y-1 mb-6">
                        <p class="text-white font-bold"><?= htmlspecialchars($payment_settings['bank_name'] ?? '') ?></p>
                        <p class="text-gray-400 text-sm">C.I: <span class="text-white font-mono"><?= htmlspecialchars($payment_settings['document_number'] ?? '') ?></span></p>
                        <p class="text-gray-400 text-sm">Telf: <span class="text-white font-mono"><?= htmlspecialchars($payment_settings['phone_number'] ?? '') ?></span></p>
                        <p class="text-gray-500 text-xs mt-2">Tasa BCV del día</p>
                    </div>
                    <button data-bs-toggle="modal" data-bs-target="#uploadProofModal" data-method="Pagomóvil" class="w-full py-2 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold transition-colors">Reportar Pago</button>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Historial -->
        <div class="lg:col-span-1">
            <h3 class="text-xl font-display font-bold text-white flex items-center gap-2 mb-6"><i class="bi bi-clock-history text-gray-400"></i> Historial</h3>
            <div class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white/5 text-gray-400 font-display uppercase text-xs">
                            <tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Estado</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="2" class="px-4 py-8 text-center text-gray-500">Sin movimientos.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $p): 
                                    $st = match($p['status']) { 'COMPLETADO'=>'text-sj-green', 'RECHAZADO'=>'text-red-400', default=>'text-orange-400' };
                                    $ic = match($p['status']) { 'COMPLETADO'=>'bi-check-circle', 'RECHAZADO'=>'bi-x-circle', default=>'bi-hourglass-split' };
                                ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="text-white font-medium"><?= (new DateTime($p['created_at']))->format('d M') ?></div>
                                        <div class="text-xs text-gray-500 font-mono">#<?= htmlspecialchars($p['reference_number']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-right"><span class="<?= $st ?> font-bold text-xs flex items-center justify-end gap-1"><i class="bi <?= $ic ?>"></i> <?= $p['status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>