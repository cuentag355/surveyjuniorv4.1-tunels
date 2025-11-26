// admin-script.js (v30.0 - Full Stable)

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. LÓGICA DE ACCIONES (BOTONES DE CONTROL)
    // ============================================================
    async function handleAdminAction(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');
        const originalHTML = btn.innerHTML;

        // Feedback visual (Spinner)
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

        const formData = new FormData(form);
        const action = formData.get('action');
        let rawValue = formData.get('value');

        // Convertir "on"/"off" a booleanos reales para la API
        let finalValue = null;
        if (rawValue === 'on') finalValue = true;
        else if (rawValue === 'off') finalValue = false;

        try {
            const response = await fetch('api_admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    value: finalValue
                })
            });

            // Intentar leer respuesta, incluso si no es 200 OK
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error("Respuesta no válida de API:", text);
                throw new Error("Error del servidor (Respuesta no válida).");
            }

            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    // Conectar Listeners a los formularios de acción
    const actionForms = [
        'maintenance-form',
        'purge-cache-form',
        'academy-form',
        'clear-logs-form',
        'force-logout-form'
    ];

    actionForms.forEach(id => {
        const form = document.getElementById(id);
        if (form) {
            // Clonar y reemplazar para asegurar limpieza de listeners antiguos
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            newForm.addEventListener('submit', handleAdminAction);
        }
    });


    // ============================================================
    // 2. GRÁFICO DE ACTIVIDAD (Manejo de Errores Mejorado)
    // ============================================================
    const ctx = document.getElementById('admin-chart');
    if (ctx) {
        fetch('api_admin_stats.php')
            .then(response => response.text()) // Leer como texto primero por seguridad
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Error JSON Gráfico:", text);
                    throw new Error("La API devolvió datos corruptos.");
                }
            })
            .then(data => {
                if (data.success) {
                    // Actualizar contadores de texto si existen
                    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    setTxt('stat-total-users', data.stats.totalUsers);
                    setTxt('stat-online-users', data.stats.onlineUsers);

                    // Renderizar gráfico si hay datos
                    if (data.chart_data && data.chart_data.jumpers) {
                        // Calcular totales de la semana para mostrar
                        const j7 = data.chart_data.jumpers.reduce((a, b) => a + b, 0);
                        const l7 = data.chart_data.logins.reduce((a, b) => a + b, 0);
                        setTxt('stat-total-jumpers', j7);
                        setTxt('stat-total-logins', l7);

                        // Crear gráfico Chart.js
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.chart_data.labels,
                                datasets: [
                                    { label: 'Jumpers', data: data.chart_data.jumpers, borderColor: '#30E8BF', backgroundColor: 'rgba(48, 232, 191, 0.1)', fill: true, tension: 0.4 },
                                    { label: 'Logins', data: data.chart_data.logins, borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.4 }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { labels: { color: '#9CA3AF' } } },
                                scales: {
                                    x: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                                    y: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
                                }
                            }
                        });
                    }
                } else {
                    throw new Error(data.message || 'Error en datos');
                }
            })
            .catch(error => {
                console.warn('Chart Error:', error);
                ctx.parentElement.innerHTML = `<div class="flex items-center justify-center h-full text-gray-500 text-sm border border-white/10 rounded bg-black/20 p-4">
                    <i class="bi bi-info-circle me-2"></i> No hay datos de gráfico disponibles.
                </div>`;
            });
    }

    // ============================================================
    // 3. GESTIÓN DE TEMA (Oscuro/Claro)
    // ============================================================
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const isDark = document.body.getAttribute('data-theme') !== 'light';
            if (isDark) { document.body.setAttribute('data-theme', 'light'); localStorage.setItem('theme', 'light'); }
            else { document.body.removeAttribute('data-theme'); localStorage.setItem('theme', 'dark'); }
        });
        if (localStorage.getItem('theme') === 'light') document.body.setAttribute('data-theme', 'light');
    }

    // ============================================================
    // 4. LÓGICA DE ACADEMIA (FIX PARA AÑADIR LECCIONES)
    // ============================================================
    const addCursoModal = document.getElementById('addCursoModal');
    if (addCursoModal) {
        addCursoModal.addEventListener('show.bs.modal', event => {
            // Botón que disparó el modal
            const button = event.relatedTarget;

            // Extraer info de los atributos data-*
            const moduloId = button.getAttribute('data-modulo-id');

            // Debug en consola (opcional)
            // console.log("Abriendo modal para añadir lección al módulo ID:", moduloId);

            // Actualizar el input hidden del modal
            const modalInput = addCursoModal.querySelector('#modal-input-modulo-id');
            if (modalInput) {
                modalInput.value = moduloId;
            } else {
                console.error("Error Crítico: No se encontró el input oculto '#modal-input-modulo-id' en el modal.");
            }
        });
    }
});