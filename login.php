<?php
// login.php (v10.0 - Quantum Gateway)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
require_once 'functions.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user'])) { header('Location: dashboard.php'); exit; }
if (function_exists('validateRememberMe')) {
    $userFromCookie = validateRememberMe($pdo);
    if ($userFromCookie) { header('Location: dashboard.php'); exit; }
}

// Obtener IP para control de intentos (Visual solamente, la lógica real está en la API)
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - SurveyJunior Quantum</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">

    <!-- Fuentes & Iconos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] },
                    colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6' } },
                    animation: { 
                        'blob': 'blob 10s infinite', 
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        blob: { 
                            '0%': { transform: 'translate(0px, 0px) scale(1)' }, 
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' }, 
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' }, 
                            '100%': { transform: 'translate(0px, 0px) scale(1)' } 
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Efectos de Vidrio y Luces */
        .glass-card {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .input-group {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .input-field {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: #30E8BF;
            background: rgba(48, 232, 191, 0.05);
            box-shadow: 0 0 0 4px rgba(48, 232, 191, 0.1);
            outline: none;
        }
        
        /* Checkbox personalizado */
        .custom-checkbox {
            appearance: none;
            background-color: rgba(255, 255, 255, 0.1);
            margin: 0;
            font: inherit;
            color: currentColor;
            width: 1.15em;
            height: 1.15em;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.25em;
            display: grid;
            place-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .custom-checkbox::before {
            content: "";
            width: 0.65em;
            height: 0.65em;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em white;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }
        
        .custom-checkbox:checked {
            background-color: #30E8BF;
            border-color: #30E8BF;
        }
        
        .custom-checkbox:checked::before {
            transform: scale(1);
        }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased h-screen w-full overflow-hidden flex items-center justify-center relative">

    <!-- FONDO AURORA (Profundidad) -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-sj-purple/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob"></div>
        <div class="absolute bottom-[-10%] right-[20%] w-[500px] h-[500px] bg-sj-blue/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute top-[40%] left-[50%] transform -translate-x-1/2 w-[300px] h-[300px] bg-sj-green/10 rounded-full mix-blend-screen filter blur-[80px] animate-pulse-slow"></div>
        <!-- Grid Overlay sutil -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-40"></div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="relative z-10 w-full max-w-md px-6">
        
        <!-- Header: Logo Animado -->
        <div class="text-center mb-8 animate-float">
            <a href="index.php" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 bg-gradient-to-br from-sj-green to-emerald-600 rounded-xl flex items-center justify-center text-sj-dark shadow-lg shadow-sj-green/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="bi bi-grid-1x2-fill text-2xl"></i>
                </div>
                <div class="text-left">
                    <h1 class="font-display font-bold text-2xl text-white tracking-tight">SurveyJunior</h1>
                    <span class="text-[10px] text-sj-green font-bold tracking-[0.2em] uppercase block">Acceso Seguro</span>
                </div>
            </a>
        </div>

        <!-- Tarjeta de Login -->
        <div class="glass-card rounded-3xl p-8 md:p-10 w-full relative overflow-hidden">
            <!-- Brillo superior -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-sj-green/50 to-transparent opacity-50"></div>

            <h2 class="text-xl font-bold text-white mb-6 text-center">Bienvenido de nuevo</h2>

            <!-- Mensaje de Error -->
            <div id="login-alert" class="hidden mb-6 bg-red-500/10 border border-red-500/20 text-red-200 text-sm rounded-xl p-4 flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-red-400 mt-0.5"></i>
                <span id="login-alert-msg">Error message here</span>
            </div>

            <form id="login-form" class="space-y-5">
                
                <!-- Campo Usuario -->
                <div class="input-group">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Usuario</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-person text-gray-500"></i>
                        </div>
                        <input type="text" name="username" class="input-field w-full rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-600" placeholder="Ingresa tu usuario" required autofocus autocomplete="username">
                    </div>
                </div>

                <!-- Campo Contraseña -->
                <div class="input-group">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-gray-500"></i>
                        </div>
                        <input type="password" name="password" id="password" class="input-field w-full rounded-xl py-3 pl-11 pr-12 text-white placeholder-gray-600" placeholder="••••••••" required autocomplete="current-password">
                        
                        <!-- Toggle Password -->
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Opciones Extra -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" name="remember_me" class="custom-checkbox">
                        <span>Recordarme</span>
                    </label>
                    <!-- <a href="#" class="text-sm text-sj-green hover:text-emerald-400 transition-colors font-medium">¿Olvidaste?</a> -->
                </div>

                <!-- Botón Submit -->
                <button type="submit" id="submit-btn" class="w-full group relative py-3.5 px-4 bg-gradient-to-r from-sj-green to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-sj-dark font-bold rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(48,232,191,0.3)] hover:shadow-[0_0_30px_rgba(48,232,191,0.5)] hover:-translate-y-0.5 flex justify-center items-center overflow-hidden">
                    <!-- Efecto brillo en botón -->
                    <div class="absolute top-0 left-[-100%] w-1/2 h-full bg-gradient-to-r from-transparent via-white/30 to-transparent transform -skew-x-12 group-hover:left-[200%] transition-all duration-700"></div>
                    
                    <span class="btn-text flex items-center gap-2 relative z-10">
                        Acceder al Sistema <i class="bi bi-arrow-right"></i>
                    </span>
                    
                    <!-- Spinner -->
                    <div class="btn-spinner hidden">
                        <svg class="animate-spin h-5 w-5 text-sj-dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>

            </form>

            <!-- Footer Links -->
            <div class="mt-8 text-center border-t border-white/10 pt-6">
                <p class="text-sm text-gray-400">
                    ¿Aún no tienes cuenta? 
                    <a href="register.php" class="text-white font-bold hover:text-sj-green transition-colors ml-1">Crear cuenta gratis</a>
                </p>
            </div>
        </div>
        
        <!-- Footer Legal -->
        <p class="text-center text-xs text-gray-600 mt-8 font-mono">
            IP: <?= htmlspecialchars($ip) ?> &bull; v10.0 Quantum
        </p>

    </div>

    <!-- TOAST CONTAINER (Notificaciones Flotantes) -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"></div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('login-form');
            const btn = document.getElementById('submit-btn');
            const btnText = btn.querySelector('.btn-text');
            const btnSpinner = btn.querySelector('.btn-spinner');
            const alertBox = document.getElementById('login-alert');
            const alertMsg = document.getElementById('login-alert-msg');
            const togglePass = document.getElementById('toggle-password');
            const passInput = document.getElementById('password');

            // 1. Toggle Password
            togglePass.addEventListener('click', () => {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                togglePass.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });

            // 2. Submit Handler
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // UI: Loading State
                btn.disabled = true;
                btnText.classList.add('hidden');
                btnSpinner.classList.remove('hidden');
                alertBox.classList.add('hidden');
                
                // Data prep
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                data.remember_me = formData.has('remember_me'); // Checkbox handling

                try {
                    const response = await fetch('api_login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Éxito: Redirección suave
                        showToast('¡Acceso concedido! Redirigiendo...', 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect || 'dashboard.php';
                        }, 800);
                    } else {
                        throw new Error(result.message || 'Error desconocido');
                    }

                } catch (error) {
                    // Error: Mostrar alerta
                    alertMsg.textContent = error.message;
                    alertBox.classList.remove('hidden');
                    
                    // Shake animation para feedback visual
                    const card = document.querySelector('.glass-card');
                    card.classList.add('animate-shake');
                    setTimeout(() => card.classList.remove('animate-shake'), 500);
                    
                } finally {
                    // UI: Restore State
                    btn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnSpinner.classList.add('hidden');
                }
            });

            // Utilidad de Toast (Notificación flotante)
            function showToast(msg, type = 'info') {
                const container = document.getElementById('toast-container');
                const colorClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
                const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                
                const toast = document.createElement('div');
                toast.className = `${colorClass} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0`;
                toast.innerHTML = `<i class="bi ${icon}"></i> <span class="font-medium text-sm">${msg}</span>`;
                
                container.appendChild(toast);
                
                // Animar entrada
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-10', 'opacity-0');
                });

                // Eliminar después de 3s
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'translate-y-2');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
    
    <style>
        /* Animación de "sacudida" para error */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
    </style>
</body>
</html>