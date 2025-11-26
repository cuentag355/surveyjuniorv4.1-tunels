<div class="modal fade" id="inactivityModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel">
            <div class="modal-header"><h5 class="modal-title font-bold text-white">Sesión inactiva</h5></div>
            <div class="modal-body text-gray-300">Cerrando sesión en <span id="inactivityCountdown" class="text-sj-green font-bold">60</span>s...</div>
            <div class="modal-footer">
                <button type="button" id="stayLoggedInBtn" class="px-4 py-2 bg-sj-blue text-white rounded-lg">Continuar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadProofModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel">
            <div class="modal-header"><h5 class="modal-title font-bold">Reportar Pago</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="modal-upload-error" class="hidden p-3 mb-3 bg-red-500/20 text-red-300 rounded"></div>
                <form id="payment-proof-form" class="space-y-4">
                    <select class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" id="payment-method" name="payment_method"><option value="Pagomóvil">Pagomóvil</option><option value="PayPal">PayPal</option></select>
                    <input type="text" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" name="reference_number" id="payment-ref" placeholder="Referencia" required>
                    <input type="text" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" name="amount" id="payment-amount" placeholder="Monto" required>
                    <input type="file" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" name="proof_image" id="payment-proof" required>
                    <textarea class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" name="notes" id="payment-notes" placeholder="Notas"></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-4 py-2 bg-sj-blue text-white rounded-lg w-full" id="submit-proof-btn">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="jumperSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-center p-4">
            <div class="modal-header border-0 justify-end"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="w-16 h-16 bg-sj-green rounded-full flex items-center justify-center mx-auto mb-4 text-sj-dark text-2xl"><i class="bi bi-check-lg"></i></div>
                <h3 class="text-2xl font-bold text-white mb-2">¡Jumper Generado!</h3>
                <div class="bg-sj-dark p-3 rounded-lg text-sj-green font-mono text-sm break-all mb-4" id="modal-jumper-link-href">...</div>
                <div class="flex gap-2 justify-center">
                    <button id="modal-btn-copy-jumper" class="px-6 py-2 bg-sj-green text-sj-dark font-bold rounded-full">Copiar</button>
                    <a id="modal-jumper-link-test" target="_blank" class="px-6 py-2 border border-white/20 text-white rounded-full hover:bg-white/10">Probar</a>
                </div>
                <div id="modal-subid-display" class="hidden"></div>
                <div id="modal-subid-author-wrapper" class="hidden"></div>
                <div id="modal-subid-pais-wrapper" class="hidden"></div>
                <div id="modal-rating-section" class="hidden mt-4 pt-4 border-t border-white/10">
                    <p class="text-gray-400 mb-2">¿Funcionó?</p>
                    <div class="flex justify-center gap-4">
                        <button class="rating-btn text-green-400 hover:text-green-300" data-rating="1"><i class="bi bi-hand-thumbs-up-fill text-2xl"></i></button>
                        <button class="rating-btn text-red-400 hover:text-red-300" data-rating="-1"><i class="bi bi-hand-thumbs-down-fill text-2xl"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="subidErrorModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><p id="modal-error-message"></p><form id="modal-add-subid-form"><input type="hidden" id="modal-add-projektnummer"><input type="text" id="modal-add-new-subid"><select id="modal-add-pais"><option value="Alemania">DE</option></select><button type="submit">Guardar</button></form></div></div></div></div>
<div class="modal fade" id="subidAddConfirmModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><span id="confirm-p"></span><span id="confirm-s"></span></div><div class="modal-footer"><button id="confirm-btn-no">No</button><button id="confirm-btn-yes">Si</button></div></div></div></div>
<div class="modal fade" id="marketMindModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><p id="marketmind-modal-text"></p><form id="modal-marketmind-form"></form></div></div></div></div>
<div class="modal fade" id="addSamplicioTokenModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content glass-panel"><div class="modal-header"><h5 class="modal-title font-bold">Añadir Token</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="modal-add-samplicio-form" class="space-y-4"><input type="text" name="hostname" id="modal-hostname" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" placeholder="Hostname" required><input type="text" name="token" id="modal-token" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" placeholder="Token" required><button type="submit" class="w-full bg-sj-blue py-2 rounded text-white">Guardar</button></form></div></div></div></div>
<div class="modal fade" id="mediaViewerModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content bg-black"><div class="modal-header border-0"><h5 class="modal-title" id="mediaViewerModalLabel"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><div id="media-viewer-content" class="w-full h-[500px]"></div></div></div></div></div>