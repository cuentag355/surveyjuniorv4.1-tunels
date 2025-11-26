// dashboard-script.js (v51.0 - FIX DEFINITIVO: SALTO VIA FORMULARIO)

document.addEventListener('DOMContentLoaded', () => {

    console.log("SurveyJunior Quantum Core: v51.0 Active (Form Jump Bypass)");

    // ============================================================
    // 1. VARIABLES Y CONFIGURACIÓN GLOBAL
    // ============================================================
    const body = document.body;
    const moduleContentContainer = document.getElementById('module-content');
    const toastContainer = document.getElementById('toast-container');
    const themeToggleButton = document.getElementById('theme-toggle-btn');
    const userAvatarButton = document.getElementById('user-avatar-btn');
    const userAvatarButtonMobile = document.getElementById('user-avatar-btn-mobile');
    const userProfileOffcanvasEl = document.getElementById('userProfileOffcanvas');
    const moduleCache = new Map();

    // Inicialización Segura de Modales
    const initModal = (id) => document.getElementById(id) ? new bootstrap.Modal(document.getElementById(id)) : null;

    window.modals = {
        inactivity: initModal('inactivityModal'),
        subidError: initModal('subidErrorModal'),
        subidAddConfirm: initModal('subidAddConfirmModal'),
        uploadProof: initModal('uploadProofModal'),
        jumperSuccess: initModal('jumperSuccessModal'),
        marketMind: initModal('marketMindModal'),
        addSamplicio: initModal('addSamplicioTokenModal'),
        mediaViewer: initModal('mediaViewerModal')
    };

    if (document.getElementById('mediaViewerModal')) {
        document.getElementById('mediaViewerModal').addEventListener('hidden.bs.modal', () => {
            const content = document.getElementById('media-viewer-content');
            if (content) content.innerHTML = '';
        });
    }

    let currentModule = 'home';
    let inactivityTimer, countdownTimer;
    const inactivityLimit = 20 * 60 * 1000; // 20 minutos

    // ============================================================
    // 2. UTILIDADES DEL SISTEMA
    // ============================================================

    async function checkSystemHealth() {
        try {
            const res = await fetch('api_ping.php');
            if (res.status === 503) {
                showToast('⛔ MANTENIMIENTO ACTIVO', 'danger');
                loadModuleContent('maintenance');
                return false;
            }
            return true;
        } catch (error) {
            loadModuleContent('maintenance');
            return false;
        }
    }

    function showToast(m, t = 'info') {
        if (!toastContainer) return;
        const id = 't-' + Date.now();
        const bg = t === 'success' ? 'bg-green-600' : (t === 'danger' ? 'bg-red-600' : (t === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'));

        toastContainer.insertAdjacentHTML('beforeend',
            `<div id="${id}" class="toast ${bg} text-white border-0 rounded-xl p-3 shadow-lg animate-fade-in flex justify-between items-center mb-2">
                <div class="flex items-center gap-2"><span>${escapeHTML(m)}</span></div>
                <button type="button" class="btn-close btn-close-white ms-2" onclick="this.parentElement.remove()"></button>
            </div>`
        );
        setTimeout(() => { const el = document.getElementById(id); if (el) el.remove() }, 4000);
    }

    function setLoading(b, l) {
        if (!b) return;
        b.disabled = l;
        const t = b.querySelector('.btn-text');
        const s = b.querySelector('.spinner-border');
        if (t) t.style.display = l ? 'none' : 'flex';
        if (s) s.style.display = l ? 'inline-block' : 'none';
    }

    function escapeHTML(s) {
        if (typeof s !== 'string') return '';
        return s.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    window.copyJumper = (t, b) => {
        navigator.clipboard.writeText(t).then(() => {
            const o = b.innerHTML;
            b.innerHTML = '<i class="bi bi-check"></i> Copiado';
            setTimeout(() => { b.innerHTML = o }, 2000);
        });
    };

    // --- ESCUCHA DE APP ELECTRON (SNIFFER) ---
    function initElectronListener() {
        if (window.electronAPI) {
            console.log("✅ Electron API detectada. Iniciando escucha...");
            window.electronAPI.onNetworkSniff((data) => {
                const event = new CustomEvent('SJ_EXTENSION_DATA', { detail: data });
                window.dispatchEvent(event);
                if (data.type === 'OS_LINK') showToast('🔗 App: Link Opensurvey Capturado', 'success');
                if (data.type === 'MP_ID') showToast('💎 App: ID Meinungsplatz Capturado', 'success');
            });
        } else {
            setTimeout(initElectronListener, 500);
        }
    }
    initElectronListener();

    // ============================================================
    // 3. MOTOR SPA
    // ============================================================

    async function loadModuleContent(moduleName, pushState = true) {
        if (!moduleContentContainer) return;
        currentModule = moduleName;

        document.querySelectorAll('.nav-link').forEach(l => {
            l.classList.remove('active', 'bg-sj-green/10', 'text-sj-green', 'border-l-2', 'border-sj-green');
            if (l.closest('.app-sidebar')) l.classList.add('text-gray-400');
            if (l.getAttribute('href').includes(`module=${moduleName}`)) {
                l.classList.add('active', 'bg-sj-green/10', 'text-sj-green', 'border-l-2', 'border-sj-green');
                l.classList.remove('text-gray-400');
            }
        });

        if (moduleCache.has(moduleName) && !['home', 'ranking', 'membership'].includes(moduleName)) {
            renderModule(moduleCache.get(moduleName), moduleName);
            if (pushState && window.location.search !== `?module=${moduleName}`) history.pushState({ module: moduleName }, '', `dashboard.php?module=${moduleName}`);
            return;
        }

        try {
            const res = await fetch(`dashboard.php?module=${moduleName}&fetch=fragment`);
            if (!res.ok) {
                if (res.status === 401) window.location.href = 'login.php';
                if (res.status === 503) { moduleContentContainer.innerHTML = await res.text(); return; }
                throw new Error(`Error ${res.status}`);
            }
            const html = await res.text();
            moduleCache.set(moduleName, html);
            renderModule(html, moduleName);
            if (pushState && window.location.search !== `?module=${moduleName}`) history.pushState({ module: moduleName }, '', `dashboard.php?module=${moduleName}`);
        } catch (e) {
            moduleContentContainer.innerHTML = `<div class='glass-panel p-6 border-l-4 border-red-500 text-white'>Error al cargar: ${e.message}</div>`;
        }
    }

    function renderModule(html, moduleName) {
        moduleContentContainer.style.opacity = '0';
        setTimeout(() => {
            moduleContentContainer.innerHTML = html;
            try { initializeModuleScripts(moduleName); }
            catch (err) { console.error(err); showToast("Error en módulo: " + err.message, 'danger'); }
            moduleContentContainer.style.opacity = '1';
            moduleContentContainer.style.transition = 'opacity 0.2s ease-out';
        }, 50);
    }

    function initializeModuleScripts(moduleName) {
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(el => new bootstrap.Tooltip(el));
        switch (moduleName) {
            case 'home': fetchHomeStats(); break;
            case 'ranking': fetchRankingData(); break;
            case 'membership': handleProofUpload(); initializeProofModalListener(); break;
            case 'academia': initializeAcademyListeners(); break;
            case 'chatbot': initializeChatbot(); break;
            case 'meinungsplatz': initializeMeinungsplatzModule(); break;
            case 'opensurvey': initializeOpensurveyModule(); break;
            case 'opinionexchange': initializeOpinionExchangeModule(); break;
            case 'horizoom': initializeHorizoomModule(); break;
            case 'samplicio': initializeSamplicioModule(); break;
            case 'marketmind': initializeMarketMindModule(); break;
            case 'm3global': initializeM3GlobalModule(); break;
        }
    }

    // ============================================================
    // 4. MÓDULO OPENSURVEY (TUNNEL FIX: FORM SUBMIT)
    // ============================================================
    function initializeOpensurveyModule() {
        const screenSelection = document.getElementById('os-selection-screen');
        const wrapperClassic = document.getElementById('os-classic-wrapper');
        const wrapperTunnel = document.getElementById('os-tunnel-wrapper');
        const btnSelectClassic = document.getElementById('btn-select-classic');
        const btnSelectTunnel = document.getElementById('btn-select-tunnel');
        const backButtons = document.querySelectorAll('.btn-back');

        if (btnSelectClassic) btnSelectClassic.onclick = () => { screenSelection.classList.add('hidden'); wrapperClassic.classList.remove('hidden'); };
        if (btnSelectTunnel) btnSelectTunnel.onclick = () => { screenSelection.classList.add('hidden'); wrapperTunnel.classList.remove('hidden'); };
        backButtons.forEach(btn => btn.onclick = () => { wrapperClassic.classList.add('hidden'); wrapperTunnel.classList.add('hidden'); screenSelection.classList.remove('hidden'); });

        // MODO CLÁSICO
        const formClassic = document.getElementById('opensurvey-classic-form');
        if (formClassic) {
            const newForm = formClassic.cloneNode(true);
            formClassic.parentNode.replaceChild(newForm, formClassic);
            newForm.addEventListener('submit', async (e) => {
                e.preventDefault(); if (!(await checkSystemHealth())) return;
                const btn = newForm.querySelector('button'); setLoading(btn, true);
                try {
                    const fd = new FormData(newForm);
                    if (!fd.has('mode')) fd.append('mode', 'classic');
                    const res = await fetch('api_generate_opensurvey.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) { showSuccessResult(data, false); newForm.reset(); } else throw new Error(data.message);
                } catch (err) { showToast(err.message, 'danger'); } finally { setLoading(btn, false); }
            });
        }

        // MODO TÚNEL
        const formTunnel = document.getElementById('opensurvey-tunnel-form');
        if (!formTunnel) return;

        const phaseInput = document.getElementById('os-phase-input');
        const phaseTunnel = document.getElementById('os-phase-tunnel');
        const phaseTimer = document.getElementById('os-phase-timer');
        const iframe = document.getElementById('os-iframe');
        const iframeLoader = document.getElementById('os-iframe-loader');
        const btnStartTimer = document.getElementById('os-btn-start-timer');
        const countdownDisplay = document.getElementById('os-countdown');
        const progressBar = document.getElementById('os-progress-bar');
        const btnForceJump = document.getElementById('os-btn-force-jump');
        const statusText = document.getElementById('os-status-text');
        const inputUrl = document.getElementById('os-input-url');

        let finalJumperUrl = '';
        let timerInterval;
        let totalSeconds = 600;
        let isWaitingForExtension = false;

        if (inputUrl) {
            const observer = new MutationObserver(() => { if (isWaitingForExtension && inputUrl.value.includes('sign=')) handleCapturedUrl(inputUrl.value); });
            observer.observe(inputUrl, { attributes: true, childList: true, characterData: true, subtree: true });
        }

        formTunnel.addEventListener('submit', async (e) => {
            e.preventDefault(); if (!(await checkSystemHealth())) return;
            const url = inputUrl.value.trim();
            const btn = formTunnel.querySelector('button');
            if (!url) return;

            setLoading(btn, true);
            inputUrl.disabled = true;

            phaseInput.classList.add('hidden');
            phaseTunnel.classList.remove('hidden');
            iframeLoader.classList.remove('hidden');

            // Limpieza inicial
            iframe.src = 'about:blank';

            if (url.includes('sign=')) {
                await prepareJumper(url);
                iframe.src = url;
                setTimeout(() => { iframeLoader.classList.add('hidden'); }, 5000);
                return;
            }

            btn.querySelector('.btn-text').textContent = "Cazando Link...";
            isWaitingForExtension = true;

            // Quitar el velo cuando cargue
            iframe.onload = () => { console.log("Iframe OS cargado"); iframeLoader.classList.add('hidden'); };

            iframe.src = url;
            showToast('Cargando encuesta...', 'info');
            setTimeout(() => iframeLoader.classList.add('hidden'), 8000);
        });

        async function handleCapturedUrl(capturedUrl) {
            if (!isWaitingForExtension) return;
            isWaitingForExtension = false;
            showToast('¡Link capturado!', 'success');
            inputUrl.value = capturedUrl;
            await prepareJumper(capturedUrl);
        }

        async function prepareJumper(fullUrl) {
            try {
                const fd = new FormData();
                fd.append('start_url', fullUrl);
                fd.append('mode', 'tunnel');
                const mins = formTunnel.querySelector('input[name="custom_minutes"]');
                fd.append('custom_minutes', mins ? mins.value : 10);

                const res = await fetch('api_generate_opensurvey.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    finalJumperUrl = data.final_jumper;
                    totalSeconds = data.wait_time;
                    updateDisplay(totalSeconds);
                    showToast(`Túnel listo. Espera: ${Math.floor(totalSeconds / 60)}m`, 'success');
                } else { throw new Error(data.message); }
            } catch (err) {
                showToast(err.message, 'danger');
                phaseTunnel.classList.add('hidden'); phaseInput.classList.remove('hidden');
                inputUrl.disabled = false; setLoading(formTunnel.querySelector('button'), false);
            }
        }

        // --- FIX DEFINITIVO: SALTO VIA FORMULARIO (Bypass de Bloqueo) ---
        const executeSafeJump = () => {
            console.log("Ejecutando Salto Seguro a:", finalJumperUrl);

            if (!finalJumperUrl || finalJumperUrl.length < 10) {
                showToast('Error: URL de Jumper perdida.', 'danger'); return;
            }
            showToast('Finalizando encuesta...', 'success');

            if (statusText) {
                statusText.className = 'bi bi-check-circle-fill text-green-500';
                statusText.nextElementSibling.textContent = 'Completando...';
            }

            // 1. Intercambio de Fases (Sin mover iframe en DOM para evitar recarga)
            phaseTimer.classList.add('hidden');
            phaseTunnel.classList.remove('hidden');

            const iframeElement = document.getElementById('os-iframe');
            iframeElement.classList.remove('hidden');
            // Forzar estilos visuales
            iframeElement.style.cssText = "display:block; width:100%; height:600px; border:4px solid #30E8BF; border-radius:12px; background-color:#FFFFFF;";

            // 2. TÉCNICA DE FORMULARIO (Bypass)
            // Asignar nombre al iframe si no lo tiene
            iframeElement.name = "target_iframe_os_" + Date.now();

            const form = document.createElement('form');
            form.method = 'GET';
            form.action = finalJumperUrl;
            form.target = iframeElement.name; // Apunta al iframe
            form.style.display = 'none';
            document.body.appendChild(form);

            form.submit();
            setTimeout(() => document.body.removeChild(form), 2000);

            // 3. Inyectar botones
            setTimeout(() => {
                showToast('¡Completado!', 'warning');

                const existingControls = document.getElementById('os-final-controls');
                if (existingControls) existingControls.remove();

                const div = document.createElement('div');
                div.id = 'os-final-controls';
                div.className = "mt-4 flex gap-3 w-full animate-fade-in";
                div.innerHTML = `
                    <button class="flex-1 px-4 py-3 bg-sj-blue hover:bg-blue-600 text-white rounded-xl font-bold shadow-lg transition-colors" onclick="location.reload()"><i class="bi bi-check-lg"></i> YA DI LIKE -> Siguiente</button>
                    <button class="px-4 py-3 bg-red-500/20 text-red-200 border border-red-500/30 rounded-xl font-bold" onclick="if(confirm('?')) location.reload()"><i class="bi bi-arrow-clockwise"></i> F5</button>
                `;

                // Insertar botones debajo del iframe en su contenedor original
                if (iframeElement.parentNode) {
                    iframeElement.parentNode.appendChild(div);
                }
            }, 3000);
        };

        if (btnStartTimer) {
            btnStartTimer.onclick = () => {
                phaseTunnel.classList.add('hidden'); phaseTimer.classList.remove('hidden');
                if (progressBar) progressBar.style.width = '0%';
                const startTime = Date.now();
                const endTime = startTime + (totalSeconds * 1000);
                updateDisplay(totalSeconds);

                timerInterval = setInterval(() => {
                    const now = Date.now();
                    const distance = endTime - now;
                    const remaining = Math.ceil(distance / 1000);
                    updateDisplay(remaining > 0 ? remaining : 0);
                    const prog = ((endTime - startTime - distance) / (endTime - startTime)) * 100;
                    if (progressBar) progressBar.style.width = `${Math.min(prog, 100)}%`;
                    if (distance <= 0) { clearInterval(timerInterval); executeSafeJump(); }
                }, 1000);
            };
        }

        if (btnForceJump) {
            const newBtn = btnForceJump.cloneNode(true); btnForceJump.parentNode.replaceChild(newBtn, btnForceJump);
            newBtn.onclick = (e) => { e.preventDefault(); if (confirm('¿Saltar?')) { clearInterval(timerInterval); executeSafeJump(); } };
        }

        function updateDisplay(sec) {
            if (!countdownDisplay) return;
            const m = Math.floor(sec / 60).toString().padStart(2, '0');
            const s = (sec % 60).toString().padStart(2, '0');
            countdownDisplay.textContent = `${m}:${s}`;
        }
    }

    // ============================================================
    // 5. MÓDULO MEINUNGSPLATZ (TUNNEL FIX: FORM SUBMIT)
    // ============================================================
    function initializeMeinungsplatzModule() {
        const screenSelection = document.getElementById('mp-selection-screen');
        const wrapperClassic = document.getElementById('mp-classic-wrapper');
        const wrapperTunnel = document.getElementById('mp-tunnel-wrapper');
        const btnSelectClassic = document.getElementById('btn-select-mp-classic');
        const btnSelectTunnel = document.getElementById('btn-select-mp-tunnel');
        const backButtons = document.querySelectorAll('.btn-back');

        if (btnSelectClassic) btnSelectClassic.onclick = () => { screenSelection.classList.add('hidden'); wrapperClassic.classList.remove('hidden'); };
        if (btnSelectTunnel) btnSelectTunnel.onclick = () => { screenSelection.classList.add('hidden'); wrapperTunnel.classList.remove('hidden'); };
        backButtons.forEach(b => b.onclick = () => { wrapperClassic.classList.add('hidden'); wrapperTunnel.classList.add('hidden'); screenSelection.classList.remove('hidden'); });

        // CLÁSICO
        const formClassic = document.getElementById('meinungsplatz-generator-form');
        if (formClassic) {
            const newForm = formClassic.cloneNode(true);
            formClassic.parentNode.replaceChild(newForm, formClassic);
            newForm.addEventListener('submit', async (e) => {
                e.preventDefault(); if (!(await checkSystemHealth())) return;
                const btn = newForm.querySelector('button'); setLoading(btn, true);
                try {
                    const u = document.getElementById('gen-urls-mp').value.trim();
                    const m = u.match(/(static-complete|complete)\?p=([0-9]{5,6})_([a-zA-Z0-9]{8,})/i);
                    if (m) { await handleCompleteJumperSubmit(m[2], m[3], u); return; }
                    const res = await fetch('api_generate.php', { method: 'POST', body: new FormData(newForm) });
                    const data = await res.json();
                    if (data.success) showSuccessResult(data, true);
                    else if (data.error_type === 'subid_not_found') showSubidModal(data.projektnummer, data.message);
                    else throw new Error(data.message);
                } catch (err) { showToast(err.message, 'danger'); } finally { if ((!window.modals.subidError || !window.modals.subidError._isShown)) setLoading(btn, false); }
            });
        }
        const mf = document.getElementById('modal-add-subid-form');
        if (mf) mf.addEventListener('submit', e => handleSubidModalSubmit(e, formClassic));

        // TÚNEL
        const formTunnel = document.getElementById('mp-tunnel-form');
        if (!formTunnel) return;

        const phaseInput = document.getElementById('mp-phase-input');
        const phaseTunnel = document.getElementById('mp-phase-tunnel');
        const phaseTimer = document.getElementById('mp-phase-timer');
        const iframe = document.getElementById('mp-iframe');
        const btnStartTimer = document.getElementById('mp-btn-start-timer');
        const statusBar = document.getElementById('mp-status-bar');
        const btnForceJump = document.getElementById('mp-btn-force-jump');
        const countdownDisplay = document.getElementById('mp-countdown');
        const progressBar = document.getElementById('mp-progress-bar');

        let tunnelData = { projekt: '', subid: '', userId: '', mins: 10, finalUrl: '' };
        let timerInterval;

        window.addEventListener('SJ_EXTENSION_DATA', (e) => {
            if (e.detail && e.detail.type === 'MP_ID' && !phaseTunnel.classList.contains('hidden')) {
                tunnelData.userId = e.detail.id;
                statusBar.innerHTML = `<span class="text-green-400 font-bold"><i class="bi bi-check-circle-fill"></i> ID Capturado: ${tunnelData.userId}</span>`;
                btnStartTimer.disabled = false;
                btnStartTimer.classList.remove('bg-gray-600', 'text-gray-400', 'cursor-not-allowed');
                btnStartTimer.classList.add('bg-green-600', 'text-white', 'hover:bg-green-500', 'animate-pulse');
                btnStartTimer.innerHTML = `<i class="bi bi-stopwatch"></i> Iniciar Espera`;
                showToast('Identidad capturada.', 'success');
            }
        });

        formTunnel.addEventListener('submit', async (e) => {
            e.preventDefault(); if (!(await checkSystemHealth())) return;
            const url = document.getElementById('mp-input-url').value.trim();
            const projekt = document.getElementById('mp-input-projekt').value.trim();
            const mins = document.querySelector('input[name="custom_minutes"]').value;
            const btn = formTunnel.querySelector('button');
            setLoading(btn, true);

            try {
                const fd = new FormData(); fd.append('action', 'check_subid'); fd.append('projektnummer', projekt);
                const res = await fetch('api_mp_tunnel.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    tunnelData = { projekt, subid: data.subid, mins, userId: '', finalUrl: '' };
                    phaseInput.classList.add('hidden'); phaseTunnel.classList.remove('hidden');
                    iframe.onload = () => { console.log("Iframe MP cargado."); };
                    iframe.src = url;
                    showToast('SubID encontrado. Cargando...', 'info');
                } else if (data.error_type === 'subid_not_found') showSubidModal(projekt, data.message);
                else throw new Error(data.message);
            } catch (err) { showToast(err.message, 'danger'); } finally { setLoading(btn, false); }
        });

        btnStartTimer.onclick = async () => {
            const fd = new FormData();
            fd.append('action', 'generate_jumper');
            fd.append('projektnummer', tunnelData.projekt);
            fd.append('subid', tunnelData.subid);
            fd.append('user_id', tunnelData.userId);
            fd.append('custom_minutes', tunnelData.mins);

            try {
                const res = await fetch('api_mp_tunnel.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    tunnelData.finalUrl = data.final_jumper;
                    const totalSeconds = data.wait_time;
                    phaseTunnel.classList.add('hidden'); phaseTimer.classList.remove('hidden');
                    startMpTimer(totalSeconds);
                } else throw new Error(data.message);
            } catch (err) { showToast(err.message, 'danger'); }
        };

        function startMpTimer(seconds) {
            const startTime = Date.now(); const endTime = startTime + (seconds * 1000);
            const updateD = (s) => { const m = Math.floor(s / 60).toString().padStart(2, '0'), sec = (s % 60).toString().padStart(2, '0'); countdownDisplay.textContent = `${m}:${sec}`; };
            updateD(seconds);
            timerInterval = setInterval(() => {
                const distance = endTime - Date.now();
                const remaining = Math.ceil(distance / 1000);
                updateD(remaining > 0 ? remaining : 0);
                const prog = ((seconds * 1000 - distance) / (seconds * 1000)) * 100;
                progressBar.style.width = `${Math.min(prog, 100)}%`;
                if (distance <= 0) { clearInterval(timerInterval); executeMpJump(); }
            }, 1000);
        }

        // --- FIX: SALTO MP VIA FORMULARIO ---
        function executeMpJump() {
            phaseTimer.classList.add('hidden');
            phaseTunnel.classList.remove('hidden');

            const iframe = document.getElementById('mp-iframe');
            iframe.classList.remove('hidden');
            iframe.style.cssText = "display:block; width:100%; height:600px; border:4px solid #30E8BF; border-radius:12px; background-color:#FFFFFF;";

            // Form Bypass
            iframe.name = "target_iframe_mp_" + Date.now();
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = tunnelData.finalUrl;
            form.target = iframe.name;
            form.style.display = 'none';
            document.body.appendChild(form);
            form.submit();
            setTimeout(() => document.body.removeChild(form), 2000);

            setTimeout(() => {
                showToast('¡Completado!', 'warning');
                const existingControls = document.getElementById('mp-final-controls');
                if (existingControls) existingControls.remove();

                const div = document.createElement('div');
                div.id = 'mp-final-controls';
                div.className = "mt-4 flex gap-3";
                div.innerHTML = `<button class="flex-1 px-4 py-3 bg-sj-blue hover:bg-blue-600 text-white rounded-xl font-bold" onclick="location.reload()"><i class="bi bi-check-lg"></i> LISTO -> Siguiente</button><button class="px-4 py-3 bg-red-500/20 text-red-200 border border-red-500/30 rounded-xl font-bold" onclick="if(confirm('?')) location.reload()"><i class="bi bi-arrow-clockwise"></i></button>`;

                if (iframe.parentNode) iframe.parentNode.appendChild(div);
            }, 3000);
        }

        if (btnForceJump) {
            const nB = btnForceJump.cloneNode(true); btnForceJump.parentNode.replaceChild(nB, btnForceJump);
            nB.onclick = () => { if (confirm('¿Saltar?')) { clearInterval(timerInterval); executeMpJump(); } };
        }
    }

    // ============================================================
    // 6. HELPERS Y OTROS MÓDULOS
    // ============================================================

    async function handleCompleteJumperSubmit(p, s, u) {
        try {
            const r = await fetch('api_check_subid.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `projektnummer=${p}&subid=${s}` });
            const d = await r.json();
            if (d.exists) showSuccessResult({ jumper: u, subid: s, projektnummer: p, added_by: 'DB', pais: 'N/A' }, true);
            else showAddConfirmModal(p, s, u);
        } catch (err) { showToast(err.message, 'danger'); }
    }

    function showAddConfirmModal(p, s, u) {
        if (!window.modals.subidAddConfirm) return;
        document.getElementById('confirm-p').textContent = p;
        document.getElementById('confirm-s').textContent = s;
        const btnYes = document.getElementById('confirm-btn-yes');
        const mainBtn = document.getElementById('mp-gen-submit-btn');
        document.getElementById('confirm-btn-no').onclick = () => { window.modals.subidAddConfirm.hide(); if (mainBtn) setLoading(mainBtn, false); };
        btnYes.onclick = async () => {
            setLoading(btnYes, true);
            try {
                const fd = new FormData();
                fd.append('pais', document.getElementById('modal-add-pais').value);
                fd.append('projektnummer', p); fd.append('new_subid', s);
                const r = await fetch('api_add_subid.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) { showToast(d.message, 'success'); showSuccessResult({ jumper: u, subid: s, projektnummer: p, added_by: 'Tú', pais: document.getElementById('modal-add-pais').value }, true); }
                else throw new Error(d.message);
            } catch (e) { showToast(e.message, 'danger'); }
            finally { window.modals.subidAddConfirm.hide(); setLoading(btnYes, false); if (mainBtn) setLoading(mainBtn, false); }
        };
        window.modals.subidAddConfirm.show();
    }

    function showSubidModal(p, msg) {
        document.getElementById('modal-error-message').innerHTML = msg;
        document.getElementById('modal-add-projektnummer').value = p;
        document.getElementById('modal-add-new-subid').value = '';
        if (window.modals.subidError) window.modals.subidError.show();
    }

    async function handleSubidModalSubmit(e, originalForm) {
        e.preventDefault();
        const btn = e.target.querySelector('button'); setLoading(btn, true);
        try {
            const r = await fetch('api_add_subid.php', { method: 'POST', body: new FormData(e.target) });
            const d = await r.json();
            if (d.success) { showToast(d.message, 'success'); if (window.modals.subidError) window.modals.subidError.hide(); if (originalForm) setTimeout(() => originalForm.requestSubmit(), 300); }
            else throw new Error(d.message);
        } catch (err) { showToast(err.message, 'danger'); } finally { setLoading(btn, false); }
    }

    function initializeOpinionExchangeModule() { const f = document.getElementById('opinionexchange-generator-form'); if (f) f.addEventListener('submit', e => simpleGenSubmit(e, 'api_generate_opinionex.php', 'input_url_opinion')); }
    function initializeHorizoomModule() { const f = document.getElementById('horizoom-generator-form'); if (f) f.addEventListener('submit', e => simpleGenSubmit(e, 'api_generate_horizoom.php')); }
    function initializeM3GlobalModule() { const f = document.getElementById('m3global-generator-form'); if (f) f.addEventListener('submit', e => simpleGenSubmit(e, 'api_generate_m3global.php')); }
    function initializeSamplicioModule() { const mf = document.getElementById('modal-add-samplicio-form'); if (mf) mf.addEventListener('submit', handleSamplicioModalSubmit); const f = document.getElementById('samplicio-generator-form'); if (f) f.addEventListener('submit', e => simpleGenSubmit(e, 'api_generate_samplicio.php')); }

    async function simpleGenSubmit(e, api, renameField = null) {
        e.preventDefault();
        if (!(await checkSystemHealth())) return;
        const btn = e.target.querySelector('button'); setLoading(btn, true);
        try {
            const fd = new FormData(e.target); if (renameField) fd.append(renameField, fd.get('urls'));
            const r = await fetch(api, { method: 'POST', body: fd }); const d = await r.json();
            if (d.success) { showSuccessResult(d, false); e.target.reset(); } else throw new Error(d.message);
        } catch (err) { showToast(err.message, 'danger'); } finally { setLoading(btn, false); }
    }

    async function handleSamplicioModalSubmit(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button'); setLoading(btn, true);
        try {
            const r = await fetch('api_samplicio_add_token.php', { method: 'POST', body: new FormData(e.target) });
            const d = await r.json();
            if (d.success) { showToast(d.message, 'success'); e.target.reset(); if (window.modals.addSamplicio) window.modals.addSamplicio.hide(); }
            else throw new Error(d.message);
        } catch (e) { showToast(e.message, 'danger'); } finally { setLoading(btn, false); }
    }

    function initializeMarketMindModule() { const f = document.getElementById('marketmind-generator-form'); if (!f) return; f.addEventListener('submit', e => { e.preventDefault(); callMarketMindApi(document.getElementById('gen-urls-marketmind').value.trim(), null, null, window.modals.marketMind); }); }
    async function callMarketMindApi(urls, sm, im, modal) {
        if (!(await checkSystemHealth())) return;
        const mb = document.getElementById('marketmind-gen-submit-btn'); const mmb = document.getElementById('modal-marketmind-submit-btn');
        let btn = (modal && modal._isShown && mmb) ? mmb : mb; if (!btn) return; setLoading(btn, true);
        try {
            const fd = new FormData(); fd.append('urls', urls); if (sm) fd.append('study_manual', sm); if (im) fd.append('id_manual', im);
            const r = await fetch('api_generate_marketmind.php', { method: 'POST', body: fd }); const d = await r.json();
            if (d.success) { if (modal && modal._isShown) modal.hide(); showSuccessResult(d, false); document.getElementById('marketmind-generator-form').reset(); }
            else if (d.error_type === 'missing_params') buildMarketMindModal(d, urls, modal); else throw new Error(d.message);
        } catch (e) { showToast(e.message, 'danger'); } finally { setLoading(btn, false); }
    }
    function buildMarketMindModal(d, u, m) {
        const f = document.getElementById('modal-marketmind-form'); f.innerHTML = '';
        const h = document.createElement('input'); h.type = 'hidden'; h.name = 'urls'; h.value = u; f.appendChild(h);
        const sVal = d.found_study || '', iVal = d.found_id || '';
        if (!sVal) f.innerHTML += `<div class="mb-3"><label class="text-white">Study</label><input type="text" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" id="modal-mm-study" required></div>`;
        if (!iVal) f.innerHTML += `<div class="mb-3"><label class="text-white">ID</label><input type="text" class="w-full bg-sj-dark border border-white/10 rounded p-2 text-white" id="modal-mm-id" required></div>`;
        f.innerHTML += `<button type="submit" id="modal-marketmind-submit-btn" class="w-full bg-sj-blue py-2 rounded text-white">Generar</button>`;
        document.getElementById('marketmind-modal-text').textContent = "Faltan datos. Ingrésalos manualmente.";
        m.show();
        f.onsubmit = (e) => { e.preventDefault(); checkSystemHealth().then(ok => { if (ok) { const s = f.querySelector('#modal-mm-study'), i = f.querySelector('#modal-mm-id'); callMarketMindApi(u, s ? s.value.trim() : sVal, i ? i.value.trim() : iVal, m); } }); };
    }

    function initializeChatbot() {
        const f = document.getElementById('chat-form'); if (f) f.addEventListener('submit', async e => {
            e.preventDefault(); const inp = document.getElementById('chat-input'); const msg = inp.value.trim(); if (!msg) return;
            addMessage('user', msg); inp.value = ''; const btn = document.getElementById('chat-submit-btn'); setLoading(btn, true);
            try {
                const r = await fetch('api_chat_assistant.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message: msg }) });
                const d = await r.json();
                addMessage('bot', d.success ? d.message : d.message, true, !d.success);
            } catch (e) { addMessage('bot', 'Error conexión', false, true); } finally { setLoading(btn, false); }
        });
    }
    function addMessage(sender, text, isHtml = false, isError = false) {
        const box = document.getElementById('chat-messages'); const div = document.createElement('div');
        if (sender === 'user') div.className = 'self-end bg-sj-blue text-white px-4 py-3 rounded-2xl rounded-br-sm max-w-[80%] shadow-lg text-sm animate-fade-in';
        else div.className = `self-start px-4 py-3 rounded-2xl rounded-bl-sm max-w-[80%] border border-white/5 text-sm leading-relaxed animate-fade-in ${isError ? 'bg-red-500/20 text-red-200' : 'bg-white/10 text-gray-200'}`;
        if (isHtml) div.innerHTML = escapeHTML(text).replace(/\*\*(.*?)\*\*/g, '<strong class="text-white">$1</strong>').replace(/\n/g, '<br>'); else div.textContent = text;
        box.appendChild(div); box.scrollTop = box.scrollHeight;
    }

    // ACADEMIA (Delegación)
    function initializeAcademyListeners() {
        const container = document.getElementById('module-content');
        if (!container) return;

        if (container.getAttribute('data-academy-listening')) return;
        container.setAttribute('data-academy-listening', 'true');

        container.onclick = (e) => {
            const btn = e.target.closest('.view-media-btn');
            if (btn) {
                e.preventDefault(); e.stopPropagation();
                if (!window.modals.mediaViewer) return;
                const mv = document.getElementById('media-viewer-content');
                const mt = document.getElementById('mediaViewerModalLabel');
                const type = btn.dataset.tipo;
                const url = btn.dataset.url;
                const title = btn.dataset.titulo;

                mv.innerHTML = '<div class="flex h-full items-center justify-center"><div class="spinner-border text-white"></div></div>';
                if (mt) mt.textContent = title;

                setTimeout(() => {
                    if (type === 'video') mv.innerHTML = `<video src="${url}" controls controlsList="nodownload" autoplay style="width:100%; height:100%; max-height:80vh; object-fit:contain;">Tu navegador no soporta video.</video>`;
                    else if (type === 'pdf') mv.innerHTML = `<iframe src="${url}#toolbar=0" style="width:100%; height:100%; border:none;"></iframe>`;
                    else if (type === 'texto') {
                        if (url.includes('<iframe')) {
                            mv.innerHTML = `<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">${url}</div>`;
                            const iframe = mv.querySelector('iframe');
                            if (iframe) { iframe.style.width = '100%'; iframe.style.height = '100%'; iframe.style.border = 'none'; iframe.removeAttribute('width'); iframe.removeAttribute('height'); }
                        } else { mv.innerHTML = `<div class="p-8 text-white overflow-y-auto h-full">${url}</div>`; }
                    }
                }, 100);
                window.modals.mediaViewer.show();
            }
        };
    }

    function showSuccessResult(data, isRatable) {
        if (!window.modals.jumperSuccess) { showToast('Jumper: ' + data.jumper, 'success'); return; }
        document.getElementById('modal-subid-display').textContent = data.subid;
        const l = document.getElementById('modal-jumper-link-href'); l.href = data.jumper; l.textContent = data.jumper;
        document.getElementById('modal-jumper-link-test').href = data.jumper;
        const cp = document.getElementById('modal-btn-copy-jumper'); cp.onclick = (e) => copyJumper(data.jumper, e.currentTarget);
        const aw = document.getElementById('modal-subid-author-wrapper'), pw = document.getElementById('modal-subid-pais-wrapper'), pe = document.getElementById('modal-subid-pais'), rs = document.getElementById('modal-rating-section');
        if (isRatable) {
            document.getElementById('modal-subid-author').textContent = data.added_by || 'Sistema';
            aw.style.display = 'inline'; aw.classList.remove('hidden');
            if (data.pais && data.pais !== 'N/A' && pe) { pe.textContent = data.pais; pw.style.display = 'inline'; pw.classList.remove('hidden'); } else { pw.style.display = 'none'; pw.classList.add('hidden'); }
            rs.style.display = 'block'; rs.classList.remove('hidden'); rs.dataset.subid = data.subid;
            rs.querySelectorAll('.rating-btn').forEach(b => { b.disabled = false; b.onclick = (ev) => handleRatingClick(ev, data.subid, rs); });
            fetch(`api_rate.php?subid=${encodeURIComponent(data.subid)}`).then(r => r.json()).then(d => { if (d.success && d.ratings) { rs.querySelector('.positive-count').textContent = d.ratings.positive; rs.querySelector('.negative-count').textContent = d.ratings.negative; } });
        } else { aw.style.display = 'none'; aw.classList.add('hidden'); pw.style.display = 'none'; pw.classList.add('hidden'); rs.style.display = 'none'; rs.classList.add('hidden'); }
        window.modals.jumperSuccess.show();
    }

    async function handleRatingClick(e, s, c) {
        const b = e.currentTarget; const r = b.dataset.rating; c.querySelectorAll('.rating-btn').forEach(btn => btn.disabled = true);
        try {
            const fd = new FormData(); fd.append('subid', s); fd.append('rating', r); const resp = await fetch('api_rate.php', { method: 'POST', body: fd }); const d = await resp.json();
            if (d.success) { showToast(d.message, 'success'); c.querySelector(r == 1 ? '.positive-count' : '.negative-count').textContent = r == 1 ? d.ratings.positive : d.ratings.negative; }
        } catch (err) { showToast(err.message, 'danger'); } finally { c.querySelectorAll('.rating-btn').forEach(btn => btn.disabled = false); }
    }

    function handleProofUpload() {
        const b = document.getElementById('submit-proof-btn'); if (b) {
            const nb = b.cloneNode(true); b.parentNode.replaceChild(nb, b);
            nb.addEventListener('click', async e => {
                e.preventDefault(); setLoading(nb, true); const f = document.getElementById('payment-proof-form');
                try {
                    const fd = new FormData(f); const pf = f.querySelector('#payment-proof').files[0]; if (pf) { fd.delete('proof'); fd.append('proof_image', pf); }
                    const r = await fetch('api_submit_proof.php', { method: 'POST', body: fd }); const d = await r.json();
                    if (d.success) {
                        showToast(d.message, 'success');
                        if (window.modals.uploadProof) window.modals.uploadProof.hide();
                        f.reset();
                        moduleCache.delete('membership');
                        setTimeout(() => loadModuleContent('membership'), 500);
                    } else throw new Error(d.message);
                } catch (err) {
                    document.getElementById('modal-upload-error').textContent = err.message;
                    document.getElementById('modal-upload-error').classList.remove('hidden');
                }
                finally { setLoading(nb, false); }
            });
        }
    }
    function initializeProofModalListener() { const m = document.getElementById('uploadProofModal'); if (m) m.addEventListener('show.bs.modal', e => { const b = e.relatedTarget; if (b && b.dataset.method) document.getElementById('payment-method').value = b.dataset.method; }); }

    // Stats & Ranking
    async function fetchHomeStats(i = true) {
        try {
            const r = await fetch('api_home_stats.php'); if (!r.ok) return; const d = await r.json();
            if (d.success && d.stats) {
                updateStatCard('stat-total-jumpers-all-time', d.stats.total_jumpers_all_time, i);
                if (document.getElementById('stat-jumpers-month')) document.getElementById('stat-jumpers-month').textContent = `Este mes: ${d.stats.total_jumpers_month}`;
                if (document.getElementById('stat-rank-name')) document.getElementById('stat-rank-name').textContent = d.stats.rank_name;
                if (document.getElementById('stat-rank-level')) document.getElementById('stat-rank-level').textContent = `Nivel ${d.stats.rank_level}`;
                if (document.getElementById('stat-total-subids')) document.getElementById('stat-total-subids').textContent = d.stats.total_subids;
                if (document.getElementById('stat-subids-rank')) document.getElementById('stat-subids-rank').textContent = `#${d.stats.subid_rank} global`;
                const c = d.country_stats || {};
                if (document.getElementById('stat-country-de')) document.getElementById('stat-country-de').textContent = (c['Alemania'] || 0) + ' subids';
                if (document.getElementById('stat-country-at')) document.getElementById('stat-country-at').textContent = (c['Austria'] || 0) + ' subids';
                if (document.getElementById('stat-country-ch')) document.getElementById('stat-country-ch').textContent = (c['Suiza'] || 0) + ' subids';
            }
        } catch (e) { }
    }
    function updateStatCard(id, val, anim) {
        const el = document.getElementById(id); if (!el) return;
        if (!anim) { el.textContent = val; return; }
        const num = parseInt(val, 10); if (isNaN(num)) { el.textContent = val; return; }
        let curr = 0, inc = Math.ceil(num / 50);
        const t = setInterval(() => { curr += inc; if (curr > num) curr = num; el.textContent = curr; if (curr == num) clearInterval(t); }, 20);
    }

    async function fetchRankingData() {
        const c = document.getElementById('ranking-podium-container'); if (!c) return;
        try {
            const r = await fetch('api_ranking.php');
            if (!r.ok) throw new Error("Error de red");
            const d = await r.json();
            if (d.success && d.ranking && d.ranking.length > 0) {
                let h = '<div class="flex justify-center items-end gap-4 mb-12 pt-10">';
                d.ranking.slice(0, 3).forEach(u => h += buildPodiumCard(u));
                h += '</div>';
                if (d.ranking.length > 3) {
                    h += '<div class="glass-panel p-6 rounded-2xl"><h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">Ranking General</h3><ul class="space-y-2">';
                    d.ranking.slice(3).forEach(u => h += buildRankingRow(u));
                    h += '</ul></div>';
                }
                c.innerHTML = h;
            } else {
                c.innerHTML = `<div class="glass-panel p-10 text-center rounded-2xl"><i class="bi bi-trophy text-4xl text-gray-600 mb-4 block"></i><p class="text-gray-400">Aún no hay colaboradores.</p></div>`;
            }
        } catch (e) { c.innerHTML = `<p class="text-red-400 text-center">${e.message}</p>`; }
    }
    function buildPodiumCard(u) {
        let h = 'h-48', b = 'border-gray-600', t = 'text-gray-400', g = '';
        if (u.rank === 1) { h = 'h-64'; b = 'border-yellow-500'; t = 'text-yellow-400'; g = 'shadow-[0_0_30px_rgba(234,179,8,0.2)]'; }
        if (u.rank === 2) { h = 'h-56'; b = 'border-gray-400'; t = 'text-gray-300'; }
        if (u.rank === 3) { h = 'h-48'; b = 'border-orange-700'; t = 'text-orange-600'; }
        return `<div class="flex flex-col items-center justify-end ${h} w-1/3 max-w-[220px] glass-panel rounded-t-3xl border-t-4 ${b} relative p-4 hover:-translate-y-2 transition-transform duration-300 ${g}"><div class="absolute -top-8"><img class="w-16 h-16 rounded-full border-4 ${b} bg-sj-dark object-cover shadow-lg" src="${u.avatar_url}"><div class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-sj-dark px-2 rounded-full border border-white/10 text-xs font-bold ${t}">#${u.rank}</div></div><div class="text-center mt-8"><div class="font-bold text-white truncate w-24 mx-auto text-sm md:text-base">${escapeHTML(u.username)}</div><div class="text-sj-green font-bold text-lg">${u.count}</div></div></div>`;
    }
    function buildRankingRow(u) {
        return `<li class="glass-panel p-4 rounded-xl flex items-center gap-4 hover:bg-white/5 transition-colors mb-3"><span class="font-display font-bold text-gray-500 w-8 text-center">#${u.rank}</span><img class="w-10 h-10 rounded-full bg-sj-dark border border-white/10" src="${u.avatar_url}"><span class="font-medium text-white flex-1 truncate">${escapeHTML(u.username)}</span><span class="font-bold text-sj-blue bg-sj-blue/10 px-3 py-1 rounded-full">${u.count}</span></li>`;
    }

    // Init
    const initP = new URLSearchParams(window.location.search);
    loadModuleContent(initP.get('module') || 'home', false);
    resetInactivityTimer();
    setInterval(() => fetch('api_ping.php').catch(() => { }), 60000);
});