<?php
// modules/internos.php
// Este archivo es llamado por dashboard.php
if (!isset($view_data) || !$view_data) {
    echo "<div class='bento-box'><p class='text-danger'>Error al cargar el módulo.</p></div>";
    return;
}

$user = $view_data['user'];
$pdo = $view_data['pdo'];

// (Opcional) Puedes añadir un control de acceso si "INTERNOS" es solo para Admin
// if ($user['membership_type'] !== 'ADMINISTRADOR') {
//     echo "<div class='bento-box'><p class='text-danger'>No tienes permiso para ver este módulo.</p></div>";
//     return;
// }

?>

<h1 class="module-title">Módulos Internos</h1>
<p class="module-subtitle">Herramientas y generadores especializados.</p>

<div class="bento-box" style="margin-bottom: 2rem; padding: 0.75rem 1.5rem;">
     <div style="display: flex; gap: 1rem; align-items: center;">
        <i class="bi bi-search" style="font-size: 1.2rem; color: var(--text-muted);"></i>
        <input type="text" id="internos-search-input" class="form-control-dark" 
               placeholder="Buscar submódulo (ej: MarketMind)..." 
               style="background: transparent; border: none; box-shadow: none; padding: 0.5rem 0; font-size: 1.1rem; width: 100%;">
     </div>
</div>


<div class="bento-grid" id="internos-module-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
    
    <a href="#" class="bento-box filterable-card" id="marketmind-card" data-target-generator="marketmind-generator-wrapper" style="text-decoration: none; color: inherit; transition: all 0.3s ease;">
        <div class="bento-box-header small" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-bar-chart-fill" style="color: var(--brand-blue);"></i>
            <span>SUBMÓDULO 1</span>
        </div>
        <h3 class="module-title" style="font-size: 1.75rem; margin: 0; color: var(--brand-blue);">MarketMind</h3>
        <p class="module-subtitle" style="font-size: 1rem; margin-top: 0.5rem;">Generador para pa.marketmind.at</p>
    </a>
    
    <a href="#" class="bento-box filterable-card" id="horizoom-card" data-target-generator="horizoom-generator-wrapper" style="text-decoration: none; color: inherit; transition: all 0.3s ease;">
        <div class="bento-box-header small" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-broadcast-pin" style="color: #c084fc;"></i>
            <span>SUBMÓDULO 2</span>
        </div>
        <h3 class="module-title" style="font-size: 1.75rem; margin: 0; color: #c084fc;">Horizoom-panel</h3>
        <p class="module-subtitle" style="font-size: 1rem; margin-top: 0.5rem;">Generador para start.horizoom.io</p>
    </a>
    
    </div>

<hr style="border-color: var(--border-color); margin: 2rem 0;">

<div id="marketmind-generator-wrapper" class="generator-container" style="display: none;">
    <div class="bento-box box-generator" style="grid-column: span 12;">
        <div class="box-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.5rem; color: var(--brand-blue); margin: 0;">
                <i class="bi bi-bar-chart-fill"></i> Generador MarketMind
            </h2>
            <button class="btn btn-sm btn-secondary close-generator-btn" data-target-generator="marketmind-generator-wrapper">
                <i class="bi bi-x-lg"></i> Cerrar
            </button>
        </div>
        
        <form id="marketmind-generator-form" class="generator-form">
            <div class="mb-3">
                <label for="gen-urls-marketmind" class="form-label">Pega tu URL de MarketMind</label>
                <textarea class="form-control-dark" id="gen-urls-marketmind" name="urls" rows="5" placeholder="https://pa.marketmind.at/pa.aspx?study=..." required></textarea>
            </div>
            
            <button type="submit" id="marketmind-gen-submit-btn" class="btn-generate" style="background-color: var(--brand-blue);">
                <span class="btn-text"><i class="bi bi-magic"></i> Generar Jumper INTERNO</span>
                <span class="spinner-border spinner-border-sm" style="display: none;" role="status" aria-hidden="true"></span>
            </button>
        </form>
    </div>
</div>

<div id="horizoom-generator-wrapper" class="generator-container" style="display: none;">
    <div class="bento-box box-generator" style="grid-column: span 12;">
        <div class="box-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.5rem; color: #c084fc; margin: 0;">
                <i class="bi bi-broadcast-pin"></i> Generador Horizoom-panel
            </h2>
            <button class="btn btn-sm btn-secondary close-generator-btn" data-target-generator="horizoom-generator-wrapper">
                <i class="bi bi-x-lg"></i> Cerrar
            </button>
        </div>
        
        <form id="horizoom-generator-form" class="generator-form" data-api-endpoint="api_generate_horizoom.php">
            <div class="mb-3">
                <label for="gen-urls-horizoom" class="form-label">Pega tu URL de Horizoom (la que tiene "i_survey" o "a")</label>
                <textarea class="form-control-dark" id="gen-urls-horizoom" name="urls" rows="5" placeholder="https://start.horizoom.io/...\nhttps://freenet.qualtrics.com/..." required></textarea>
            </div>
            
            <button type="submit" id="horizoom-gen-submit-btn" class="btn-generate" style="background-color: #c084fc;">
                <span class="btn-text"><i class="bi bi-magic"></i> Generar Jumper INTERNO</span>
                <span class="spinner-border spinner-border-sm" style="display: none;" role="status" aria-hidden="true"></span>
            </button>
        </form>
    </div>
</div>