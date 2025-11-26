<?php
// index.php (v10.0 - Quantum Landing Page)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
require_once 'functions.php';

// Redirección si ya está logueado
if (isset($_SESSION['user'])) { header('Location: dashboard.php'); exit; }
if (function_exists('validateRememberMe')) {
    $userFromCookie = validateRememberMe($pdo);
    if ($userFromCookie) { header('Location: dashboard.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SurveyJunior - Arquitectura de Encuestas</title>
    
    <meta name="description" content="Plataforma de optimización de encuestas. Generadores inteligentes para Meinungsplatz, Samplicio, Opensurvey y más.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">

    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap (Solo para Modal y Toasts) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6', orange: '#F97316' } },
                    animation: { 'blob': 'blob 7s infinite', 'fade-in': 'fadeIn 1s ease-out', 'float': 'float 6s ease-in-out infinite' },
                    keyframes: {
                        blob: { '0%': { transform: 'translate(0px, 0px) scale(1)' }, '33%': { transform: 'translate(30px, -50px) scale(1.1)' }, '66%': { transform: 'translate(-20px, 20px) scale(0.9)' }, '100%': { transform: 'translate(0px, 0px) scale(1)' } },
                        fadeIn: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-20px)' } }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #030712; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
        
        /* Glassmorphism */
        .glass-nav { background: rgba(3, 7, 18, 0.6); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .glass-card { background: rgba(17, 24, 39, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); }
        
        /* Modal Override */
        .modal-content { background-color: #111827 !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; border-radius: 1.5rem !important; }
        .modal-header, .modal-footer { border-color: rgba(255,255,255,0.05) !important; }
        .btn-close { filter: invert(1); }
        .form-control { background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; }
        .form-control:focus { border-color: #30E8BF !important; box-shadow: 0 0 0 0.25rem rgba(48, 232, 191, 0.25) !important; }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased selection:bg-sj-green selection:text-sj-dark overflow-x-hidden font-sans">

    <!-- Fondo Aurora -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] bg-sj-purple/20 rounded-full mix-blend-screen filter blur-[100px] animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-[400px] h-[400px] bg-sj-blue/20 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-[600px] h-[600px] bg-sj-green/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-4000"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-30"></div>
    </div>

    <!-- Navegación -->
    <nav class="fixed top-0 w-full z-50 glass-nav transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-sj-green to-emerald-600 rounded-xl flex items-center justify-center text-sj-dark shadow-lg shadow-sj-green/20 group-hover:scale-110 transition-transform">
                    <i class="bi bi-grid-1x2-fill text-xl"></i>
                </div>
                <span class="font-display font-bold text-xl text-white tracking-tight">SurveyJunior <span class="text-sj-green text-xs uppercase tracking-widest ml-1 opacity-70">Quantum</span></span>
            </a>
            
            <div class="flex items-center gap-4">
                <button data-bs-toggle="modal" data-bs-target="#loginModal" class="hidden md:block px-4 py-2 text-gray-300 hover:text-white font-medium transition-colors">
                    Iniciar Sesión
                </button>
                <a href="register.php" class="px-6 py-2.5 bg-white text-sj-dark hover:bg-gray-200 font-bold rounded-full transition-all shadow-lg shadow-white/10 hover:scale-105">
                    Crear Cuenta
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative z-10 pt-40 pb-20 px-6">
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
            
            <!-- Texto Hero -->
            <div class="text-center lg:text-left animate-fade-in">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sj-green text-sm font-bold mb-6">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sj-green opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-sj-green"></span>
                    </span>
                    Arquitectura v10.0 Disponible
                </div>
                
                <h1 class="font-display text-5xl md:text-7xl font-bold text-white leading-tight mb-6">
                    Domina el flujo de <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-sj-green via-blue-500 to-purple-600 animate-gradient">Tus Encuestas</span>
                </h1>
                
                <p class="text-gray-400 text-lg md:text-xl mb-10 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    La plataforma definitiva para analistas de datos y panelistas. Generación inteligente de jumpers, base de datos colaborativa y herramientas de automatización.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="register.php" class="px-8 py-4 bg-sj-green hover:bg-emerald-400 text-sj-dark font-bold text-lg rounded-2xl transition-all shadow-[0_0_30px_rgba(48,232,191,0.3)] hover:shadow-[0_0_50px_rgba(48,232,191,0.5)] hover:-translate-y-1 flex items-center justify-center gap-2">
                        <i class="bi bi-rocket-takeoff-fill"></i> Comienza Gratis
                    </a>
                    <button data-bs-toggle="modal" data-bs-target="#loginModal" class="px-8 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold text-lg rounded-2xl transition-all hover:-translate-y-1 flex items-center justify-center gap-2 backdrop-blur-md">
                        <i class="bi bi-box-arrow-in-right"></i> Acceder
                    </button>
                </div>
            </div>

            <!-- Visual: Terminal Simulada -->
            <div class="relative animate-float hidden lg:block">
                <!-- Efecto Glow Detrás -->
                <div class="absolute -inset-4 bg-gradient-to-r from-sj-green/30 to-sj-purple/30 rounded-[2rem] blur-2xl opacity-50"></div>
                
                <div class="glass-card rounded-2xl overflow-hidden border border-white/20 shadow-2xl relative z-10">
                    <!-- Terminal Header -->
                    <div class="bg-black/40 px-4 py-3 flex items-center gap-2 border-b border-white/10">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <div class="flex-1 text-center text-xs text-gray-500 font-mono">survey_engine.exe</div>
                    </div>
                    
                    <!-- Terminal Body -->
                    <div class="bg-sj-dark/90 p-6 font-mono text-sm md:text-base h-[300px] flex flex-col">
                        <div id="code-container" class="space-y-2">
                            <!-- El JS escribirá aquí -->
                        </div>
                        <div class="mt-2 animate-pulse text-sj-green">_</div>
                    </div>
                </div>
                
                <!-- Tarjetas Flotantes Decorativas -->
                <div class="absolute -right-10 -bottom-10 glass-card p-4 rounded-xl flex items-center gap-3 animate-bounce animation-delay-2000 border border-sj-green/30 bg-sj-dark/80">
                    <div class="w-10 h-10 rounded-full bg-sj-green/20 flex items-center justify-center text-sj-green"><i class="bi bi-check-lg"></i></div>
                    <div>
                        <div class="text-xs text-gray-400">Estado</div>
                        <div class="text-white font-bold">Jumper Generado</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="relative z-10 py-24 bg-black/20 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">Tecnología de Vanguardia</h2>
                <p class="text-gray-400">Herramientas diseñadas para maximizar tu eficiencia.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass-card p-8 rounded-3xl hover:bg-white/5 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-sj-blue/20 rounded-2xl flex items-center justify-center text-sj-blue text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Motor Inteligente</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Nuestro algoritmo detecta automáticamente el tipo de encuesta (Meinungsplatz, Samplicio, etc.) y extrae los parámetros necesarios sin que muevas un dedo.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="glass-card p-8 rounded-3xl hover:bg-white/5 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-sj-purple/20 rounded-2xl flex items-center justify-center text-sj-purple text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-database-fill-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">DB Colaborativa</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Accede a miles de Tokens y SubIDs compartidos por la comunidad. Si un jumper falla, el sistema aprende y se actualiza.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="glass-card p-8 rounded-3xl hover:bg-white/5 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-sj-orange/20 rounded-2xl flex items-center justify-center text-sj-orange text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Academia Pro</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Aprende las mejores estrategias con nuestra biblioteca de videos y guías exclusivas para miembros PRO.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="relative z-10 py-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> SurveyJunior Quantum. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Modal Login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border border-white/20 shadow-2xl bg-sj-dark/90">
                <div class="modal-header border-b border-white/10">
                    <h5 class="modal-title font-display font-bold">Acceso Quantum</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-8">
                    <div id="login-error-msg" class="hidden p-3 mb-4 bg-red-500/20 border border-red-500/50 text-red-200 rounded-lg text-sm"></div>
                    
                    <form id="ajax-login-form" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Usuario</label>
                            <input type="text" name="username" class="form-control w-full rounded-xl p-3 bg-black/30 border-white/10 text-white focus:border-sj-green focus:ring-1 focus:ring-sj-green transition-all" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Contraseña</label>
                            <input type="password" name="password" class="form-control w-full rounded-xl p-3 bg-black/30 border-white/10 text-white focus:border-sj-green focus:ring-1 focus:ring-sj-green transition-all" required>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                                <input type="checkbox" name="remember_me" class="rounded bg-black/30 border-white/20 text-sj-green focus:ring-sj-green"> Recordarme
                            </label>
                        </div>
                        
                        <button type="submit" id="login-submit-btn" class="w-full py-3 bg-sj-green hover:bg-emerald-400 text-sj-dark font-bold rounded-xl transition-all shadow-lg shadow-sj-green/20 flex justify-center items-center gap-2">
                            <span id="login-btn-text">Iniciar Sesión</span>
                            <div id="login-btn-spinner" class="w-5 h-5 border-2 border-sj-dark/30 border-t-sj-dark rounded-full animate-spin hidden"></div>
                        </button>
                    </form>
                </div>
                <div class="modal-footer border-t border-white/10 justify-center bg-black/20">
                    <p class="text-sm text-gray-400">¿Nuevo aquí? <a href="register.php" class="text-sj-green hover:underline font-bold">Crear cuenta gratis</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toasts Container -->
    <div class="toast-container position-fixed bottom-4 right-4 p-3" id="public-toast-container" style="z-index: 1100;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script de Animación de Código y Login -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // --- Animación Terminal ---
            const codeContainer = document.getElementById('code-container');
            if(codeContainer) {
                const lines = [
                    { text: "// Analizando URL...", color: "text-gray-500" },
                    { text: "DETECTED: Meinungsplatz Protocol", color: "text-blue-400" },
                    { text: "> Extracting Projektnummer...", color: "text-white" },
                    { text: "SUCCESS: ID 123456 found", color: "text-green-400" },
                    { text: "> Searching database...", color: "text-white" },
                    { text: "MATCH: SubID '9a8b7c' retrieved", color: "text-yellow-400" },
                    { text: "> Building Jumper...", color: "text-white" },
                    { text: "DONE: Redirecting user...", color: "text-sj-green font-bold" }
                ];
                
                let lineIndex = 0;
                
                function typeLine() {
                    if (lineIndex >= lines.length) {
                        setTimeout(() => { codeContainer.innerHTML = ''; lineIndex = 0; typeLine(); }, 5000);
                        return;
                    }
                    
                    const lineData = lines[lineIndex];
                    const div = document.createElement('div');
                    div.className = `${lineData.color} font-mono`;
                    div.textContent = '> ';
                    codeContainer.appendChild(div);
                    
                    let charIndex = 0;
                    const typeChar = () => {
                        if (charIndex < lineData.text.length) {
                            div.textContent += lineData.text.charAt(charIndex);
                            charIndex++;
                            setTimeout(typeChar, 30); // Velocidad de escritura
                        } else {
                            lineIndex++;
                            setTimeout(typeLine, 600); // Espera entre líneas
                        }
                    };
                    typeChar();
                }
                typeLine();
            }

            // --- Lógica de Login AJAX ---
            const loginForm = document.getElementById('ajax-login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = document.getElementById('login-submit-btn');
                    const txt = document.getElementById('login-btn-text');
                    const spn = document.getElementById('login-btn-spinner');
                    const err = document.getElementById('login-error-msg');
                    
                    btn.disabled = true;
                    txt.style.display = 'none';
                    spn.style.display = 'block';
                    err.classList.add('hidden');

                    const formData = new FormData(loginForm);
                    const dataToSend = Object.fromEntries(formData.entries());
                    dataToSend.remember_me = formData.has('remember_me');

                    try {
                        const res = await fetch('api_login.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(dataToSend)
                        });
                        const data = await res.json();
                        
                        if (res.ok && data.success) {
                            window.location.href = 'dashboard.php';
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    } catch (error) {
                        err.textContent = error.message;
                        err.classList.remove('hidden');
                        btn.disabled = false;
                        txt.style.display = 'block';
                        spn.style.display = 'none';
                    }
                });
            }

            // --- Live Activity Toasts ---
            async function fetchActivity() {
                try {
                    const r = await fetch('api_public_activity.php');
                    if(!r.ok) return;
                    const d = await r.json();
                    if(d.success && d.activities.length > 0) {
                        const act = d.activities[Math.floor(Math.random() * d.activities.length)];
                        showToast(act.user, act.message);
                    }
                } catch(e){}
            }
            
            function showToast(user, msg) {
                const container = document.getElementById('public-toast-container');
                const id = 't-' + Date.now();
                const html = `
                    <div id="${id}" class="glass-card p-4 rounded-2xl border border-white/10 shadow-2xl animate-fade-in mb-3 min-w-[280px]">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-sj-green/20 rounded-full flex items-center justify-center text-sj-green shrink-0">
                                <i class="bi bi-activity"></i>
                            </div>
                            <div>
                                <div class="text-sm text-white font-bold">${user}</div>
                                <div class="text-xs text-gray-400">${msg}</div>
                            </div>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
                setTimeout(() => { document.getElementById(id)?.remove(); }, 5000);
            }

            // Iniciar loop de actividad
            setInterval(fetchActivity, 8000);
        });
    </script>
</body>
</html>