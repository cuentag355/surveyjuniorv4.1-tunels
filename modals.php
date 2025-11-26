<!-- includes/modals.php (v10.0 - Quantum Design Final) -->

<!-- 1. Modal del Reproductor (ACADEMIA) -->
<!-- Este modal es crucial para que los videos y PDFs se vean -->
<div class="modal fade" id="mediaViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl"> 
        <div class="modal-content glass-panel border-0 overflow-hidden bg-black">
            <div class="modal-header border-b border-white/10 bg-black/40">
                <h5 class="modal-title font-bold text-white" id="mediaViewerModalLabel">Academia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-black relative" style="height: 70vh;">
                <!-- Aquí se inyecta el video/pdf vía JS -->
                <div id="media-viewer-content" class="w-full h-full flex items-center justify-center text-white" style="width: 100%; height: 100%;">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Modal de Éxito (GENERADORES) -->
<div class="modal fade" id="jumperSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border border-sj-green/30 shadow-[0_0_50px_rgba(48,232,191,0.2)] bg-sj-card">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pb-8 px-6">
                <div class="w-20 h-20 bg-gradient-to-br from-sj-green to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-sj-green/20 animate-blob">
                    <i class="bi bi-rocket-launch-fill text-3xl text-sj-dark"></i>
                </div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">¡Jumper Generado!</h3>
                
                <!-- Info del SubID -->
                <div class="text-sm text-gray-400 mb-4">
                    SubID: <strong id="modal-subid-display" class="text-white">...</strong>
                    <span id="modal-subid-author-wrapper" class="hidden ml-1">(por <span id="modal-subid-author" class="text-sj-green">...</span>)</span>
                    <span id="modal-subid-pais-wrapper" class="hidden ml-1 text-blue-400 font-bold"> <i class="bi bi-globe-americas"></i> <span id="modal-subid-pais"></span></span>
                </div>

                <!-- Caja del Link -->
                <div class="bg-black/40 border border-white/10 rounded-xl p-4 mb-6 break-all font-mono text-sj-green text-sm relative group hover:bg-black/60 transition-colors">
                    <a href="#" id="modal-jumper-link-href" target="_blank" class="hover:underline decoration-sj-green underline-offset-4">...</a>
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="bi bi-box-arrow-up-right text-gray-500"></i>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="flex gap-3 justify-center mb-6">
                    <button id="modal-btn-copy-jumper" class="px-6 py-2.5 bg-sj-green hover:bg-emerald-400 text-sj-dark font-bold rounded-xl transition-colors flex items-center gap-2 shadow-lg shadow-sj-green/20">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                    <a id="modal-jumper-link-test" target="_blank" class="px-6 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium rounded-xl transition-colors">
                        Probar
                    </a>
                </div>

                <!-- Sección de Rating -->
                <div id="modal-rating-section" class="hidden pt-6 border-t border-white/10">
                    <p class="text-gray-400 text-sm mb-3">¿Funcionó este SubID?</p>
                    <div class="flex justify-center gap-4">
                        <button class="rating-btn flex items-center gap-2 px-4 py-2 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 transition-colors border border-green-500/20" data-rating="1">
                            <i class="bi bi-hand-thumbs-up-fill"></i> <span class="positive-count">0</span>
                        </button>
                        <button class="rating-btn flex items-center gap-2 px-4 py-2 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors border border-red-500/20" data-rating="-1">
                            <i class="bi bi-hand-thumbs-down-fill"></i> <span class="negative-count">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal Reportar Pago (MEMBRESÍA) -->
<div class="modal fade" id="uploadProofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 bg-sj-card">
            <div class="modal-header border-b border-white/10 bg-black/20">
                <h5 class="modal-title font-bold text-white">Reportar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <div id="modal-upload-error" class="hidden p-3 mb-4 bg-red-500/20 border border-red-500/50 text-red-200 rounded-lg text-sm flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> <span>Error</span>
                </div>
                
                <form id="payment-proof-form" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-wider">Método</label>
                        <select class="w-full bg-sj-dark border border-white/10 rounded-xl p-3 text-white focus:border-sj-blue focus:ring-1 focus:ring-sj-blue outline-none appearance-none" id="payment-method" name="payment_method">
                            <option value="Pagomóvil">Pagomóvil (Bs.)</option>
                            <option value="PayPal">PayPal (USD)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-wider">Referencia</label>
                            <input type="text" class="w-full bg-sj-dark border border-white/10 rounded-xl p-3 text-white focus:border-sj-blue outline-none placeholder-gray-600" name="reference_number" id="payment-ref" placeholder="Ej: 000123" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-wider">Monto</label>
                            <input type="text" class="w-full bg-sj-dark border border-white/10 rounded-xl p-3 text-white focus:border-sj-blue outline-none placeholder-gray-600" name="amount" id="payment-amount" placeholder="0.00" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-wider">Comprobante (Imagen)</label>
                        <input type="file" class="w-full bg-sj-dark border border-white/10 rounded-xl p-2 text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-white/10 file:text-white hover:file:bg-white/20 transition-colors cursor-pointer" name="proof_image" id="payment-proof" accept="image/*" required>
                    </div>
                    <div>
                         <label class="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-wider">Nota (Opcional)</label>
                         <textarea class="w-full bg-sj-dark border border-white/10 rounded-xl p-3 text-white focus:border-sj-blue outline-none resize-none placeholder-gray-600" name="notes" id="payment-notes" rows="2" placeholder="Detalles adicionales..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-t border-white/10 bg-black/20">
                <button type="button" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="px-6 py-2 bg-sj-blue hover:bg-blue-600 text-white font-bold rounded-lg shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2" id="submit-proof-btn">
                    <span class="btn-text">Enviar Reporte</span>
                    <span class="spinner-border w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin hidden"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 4. Modal de Inactividad -->
<div class="modal fade" id="inactivityModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-l-4 border-sj-yellow bg-sj-card">
            <div class="modal-body p-6">
                <h5 class="text-xl font-bold text-white mb-2 flex items-center gap-2"><i class="bi bi-clock-history text-sj-yellow"></i> ¿Sigues ahí?</h5>
                <p class="text-gray-400 mb-6">Tu sesión se cerrará automáticamente en <span id="inactivityCountdown" class="text-sj-yellow font-bold">60</span> segundos por inactividad.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-gray-400 hover:text-white transition-colors" id="logoutBtn">Cerrar Sesión</button>
                    <button type="button" class="px-6 py-2 bg-sj-yellow text-sj-dark font-bold rounded-lg hover:bg-yellow-300 transition-colors shadow-lg shadow-yellow-500/20" id="stayLoggedInBtn">Continuar Aquí</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. Modal Error SubID (Meinungsplatz) -->
<div class="modal fade" id="subidErrorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel bg-sj-card border border-red-500/30">
            <div class="modal-header border-b border-white/10">
                <h5 class="text-white font-bold flex items-center gap-2"><i class="bi bi-exclamation-circle text-red-400"></i> SubID No Encontrado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <p id="modal-error-message" class="text-gray-300 mb-4 text-sm"></p>
                <form id="modal-add-subid-form" class="space-y-4">
                    <input type="hidden" id="modal-add-projektnummer" name="projektnummer">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500 uppercase font-bold">Nuevo SubID</label>
                        <input type="text" id="modal-add-new-subid" name="new_subid" class="w-full bg-sj-dark border border-white/10 rounded-lg p-2.5 text-white focus:border-sj-green outline-none" placeholder="Ej: a1b2c3d4" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500 uppercase font-bold">País</label>
                        <select id="modal-add-pais" name="pais" class="w-full bg-sj-dark border border-white/10 rounded-lg p-2.5 text-white focus:border-sj-green outline-none">
                            <option value="Alemania">Alemania (DE)</option>
                            <option value="Austria">Austria (AT)</option>
                            <option value="Suiza">Suiza (CH)</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-sj-green hover:bg-emerald-400 text-sj-dark font-bold py-2.5 rounded-lg mt-2 transition-colors shadow-lg shadow-sj-green/20">
                        Guardar y Continuar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 6. Modal Confirmar SubID (Meinungsplatz) -->
<div class="modal fade" id="subidAddConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel bg-sj-card">
            <div class="modal-header border-b border-white/10">
                <h5 class="text-white font-bold">Confirmar Nuevo SubID</h5>
            </div>
            <div class="modal-body p-6">
                <p class="text-gray-300 mb-4 text-sm">Se detectó un SubID nuevo en la URL. ¿Deseas guardarlo en la base de datos?</p>
                <div class="bg-white/5 p-3 rounded-lg border border-white/10 mb-4 font-mono text-sm">
                    <div class="text-gray-400">P: <span id="confirm-p" class="text-white font-bold"></span></div>
                    <div class="text-gray-400">S: <span id="confirm-s" class="text-white font-bold"></span></div>
                </div>
                <div class="flex justify-end gap-3">
                    <button id="confirm-btn-no" class="text-gray-400 hover:text-white px-4 py-2 text-sm">No, ignorar</button>
                    <button id="confirm-btn-yes" class="bg-sj-green hover:bg-emerald-400 text-sj-dark font-bold px-6 py-2 rounded-lg shadow-lg shadow-sj-green/20 text-sm transition-colors">Sí, Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 7. Modal MarketMind (Faltan Datos) -->
<div class="modal fade" id="marketMindModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel bg-sj-card border border-yellow-500/30">
            <div class="modal-header border-b border-white/10">
                <h5 class="text-white font-bold flex items-center gap-2"><i class="bi bi-exclamation-triangle text-sj-yellow"></i> Datos Faltantes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <p id="marketmind-modal-text" class="text-gray-300 mb-4 text-sm"></p>
                <form id="modal-marketmind-form" class="space-y-4">
                    <!-- Los campos se inyectan dinámicamente via JS -->
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 8. Modal Samplicio (Añadir Token) -->
<div class="modal fade" id="addSamplicioTokenModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel bg-sj-card border border-sj-purple/30">
            <div class="modal-header border-b border-white/10">
                <h5 class="text-white font-bold flex items-center gap-2"><i class="bi bi-key-fill text-sj-purple"></i> Añadir Token</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <form id="modal-add-samplicio-form" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500 uppercase font-bold">Hostname</label>
                        <input type="text" id="modal-hostname" name="hostname" class="w-full bg-sj-dark border border-white/10 rounded-lg p-2.5 text-white focus:border-sj-purple outline-none" placeholder="survey.site.com" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500 uppercase font-bold">Token</label>
                        <input type="text" id="modal-token" name="token" class="w-full bg-sj-dark border border-white/10 rounded-lg p-2.5 text-white focus:border-sj-purple outline-none" placeholder="Token secreto" required>
                    </div>
                    <button type="submit" class="w-full bg-sj-blue hover:bg-blue-600 text-white font-bold py-2.5 rounded-lg mt-2 transition-colors shadow-lg shadow-blue-600/20">
                        Guardar Token
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>