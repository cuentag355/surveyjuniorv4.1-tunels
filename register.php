<?php
// register.php (v12.0 - Quantum Registration Final)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
require_once 'functions.php';

// Detección de AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Redirección si ya está logueado
if (isset($_SESSION['user'])) {
    if ($is_ajax) { echo json_encode(['success' => true, 'redirect' => 'dashboard.php']); exit; }
    header('Location: dashboard.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer datos (JSON o POST normal)
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $password_confirm = $input['password_confirm'] ?? '';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
    }

    // Validaciones
    if (empty($username) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'El usuario solo puede tener letras, números y guiones bajos.';
    } else {
        try {
            // Verificar duplicados
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'El nombre de usuario ya está en uso.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // --- LÓGICA CRÍTICA: 5 JUMPERS DE PRUEBA ---
                // Insertamos explícitamente jumper_limit = 5 y membership_type = 'PRUEBA GRATIS'
                try {
                    // Intento 1: Con columna created_at (Ideal)
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios 
                        (username, password, membership_type, jumper_count, jumper_limit, active, banned, created_at) 
                        VALUES (?, ?, 'PRUEBA GRATIS', 0, 5, 1, 0, NOW())
                    ");
                    $stmt->execute([$username, $hash]);
                } catch (PDOException $e) {
                    // Intento 2: Fallback por si la columna 'created_at' no existe
                    if (strpos($e->getMessage(), 'Unknown column') !== false) {
                        $stmt = $pdo->prepare("
                            INSERT INTO usuarios 
                            (username, password, membership_type, jumper_count, jumper_limit, active, banned) 
                            VALUES (?, ?, 'PRUEBA GRATIS', 0, 5, 1, 0)
                        ");
                        $stmt->execute([$username, $hash]);
                    } else {
                        throw $e; // Relanzar si es otro error
                    }
                }

                if (function_exists('logActivity')) logActivity($pdo, $pdo->lastInsertId(), $username, 'Registro Exitoso');
                
                if ($is_ajax) {
                    echo json_encode(['success' => true, 'message' => '¡Cuenta creada! Tienes 5 jumpers gratis.', 'redirect' => 'login.php']);
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log("Error Registro: " . $e->getMessage());
            $error = 'Error de base de datos al registrar.';
        }
    }

    if ($is_ajax && $error) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SurveyJunior Quantum</title>
    
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="alternate icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='3' y='3' width='18' height='18' rx='4' fill='%230A0E1A' opacity='0.1'/%3E%3Cpath d='M16 8C16 6.89543 15.1046 6 14 6H10C8.89543 6 8 6.89543 8 8C8 9.10457 8.89543 10 10 10H14' stroke='%2330E8BF' stroke-width='2.5' stroke-linecap='round'/%3E%3Cpath d='M8 16C8 17.1046 8.89543 18 10 18H14C15.1046 18 16 17.1046 16 16C16 14.8954 15.1046 14 14 14H10' stroke='%2330E8BF' stroke-width='2.5' stroke-linecap='round'/%3E%3C/svg%3E">

    <!-- Fuentes -->
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
                    colors: { sj: { dark: '#030712', card: 'rgba(17, 24, 39, 0.7)', green: '#30E8BF', blue: '#3B82F6', purple: '#8B5CF6', red: '#F47174', yellow: '#FACC15' } },
                    animation: { 'blob': 'blob 10s infinite', 'float': 'float 6s ease-in-out infinite' },
                    keyframes: {
                        blob: { '0%': { transform: 'translate(0px, 0px) scale(1)' }, '33%': { transform: 'translate(30px, -50px) scale(1.1)' }, '66%': { transform: 'translate(-20px, 20px) scale(0.9)' }, '100%': { transform: 'translate(0px, 0px) scale(1)' } },
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } }
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass-card {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
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
        .strength-bar { height: 4px; border-radius: 2px; transition: all 0.3s ease; background: #374151; }
        .strength-bar.active { background: #30E8BF; } .strength-bar.weak { background: #F47174; } .strength-bar.medium { background: #FACC15; } .strength-bar.strong { background: #30E8BF; }
    </style>
</head>
<body class="bg-sj-dark text-gray-200 antialiased h-screen w-full overflow-hidden flex items-center justify-center relative">

    <!-- FONDO AURORA -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-sj-blue/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-[500px] h-[500px] bg-sj-purple/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute top-[40%] left-[50%] transform -translate-x-1/2 w-[300px] h-[300px] bg-sj-green/10 rounded-full mix-blend-screen filter blur-[80px] animate-pulse"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0ibm9uZSIvPgo8Y2lyY2xlIGN4PSI1IiBjeT0iNSIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPgo8L3N2Zz4=')] opacity-40"></div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="relative z-10 w-full max-w-md px-6">
        
        <!-- Header -->
        <div class="text-center mb-6 animate-float">
            <a href="index.php" class="inline-flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-sj-purple to-indigo-600 rounded-xl flex items-center justify-center text-sj-dark shadow-lg shadow-sj-purple/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="bi bi-stars text-xl"></i>
                </div>
                <div class="text-left">
                    <h1 class="font-display font-bold text-2xl text-white tracking-tight">Únete a SurveyJunior</h1>
                    <span class="text-[10px] text-sj-green font-bold tracking-[0.2em] uppercase block">Prueba Gratuita</span>
                </div>
            </a>
        </div>

        <!-- Tarjeta de Registro -->
        <div class="glass-card rounded-3xl p-8 w-full relative overflow-hidden border border-white/10">
            
            <h2 class="text-lg font-bold text-white mb-2 text-center">Crea tu cuenta en segundos</h2>
            <p class="text-sm text-gray-400 text-center mb-6">Te regalamos <strong>5 jumpers</strong> para empezar.</p>

            <!-- Mensajes de Estado -->
            <div id="alert-box" class="hidden mb-6 rounded-xl p-4 flex items-start gap-3 text-sm">
                <i class="bi mt-0.5 text-lg"></i>
                <span id="alert-msg"></span>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-200 rounded-xl p-4 mb-6 text-sm flex gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-red-400"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="register-form" class="space-y-4">
                
                <!-- Usuario -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Usuario</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="bi bi-person text-gray-500"></i></div>
                        <input type="text" name="username" class="input-field w-full rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-600" placeholder="Elige un nombre único" required pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guiones bajos" autocomplete="off">
                    </div>
                </div>

                <!-- Contraseña -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="bi bi-lock text-gray-500"></i></div>
                        <input type="password" name="password" id="password" class="input-field w-full rounded-xl py-3 pl-11 pr-12 text-white placeholder-gray-600" placeholder="Mínimo 6 caracteres" required minlength="6" autocomplete="new-password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors" data-target="password"><i class="bi bi-eye"></i></button>
                    </div>
                    <!-- Medidor de Fuerza -->
                    <div class="flex gap-1 mt-2 h-1">
                        <div class="strength-bar w-1/4" id="bar-1"></div>
                        <div class="strength-bar w-1/4" id="bar-2"></div>
                        <div class="strength-bar w-1/4" id="bar-3"></div>
                        <div class="strength-bar w-1/4" id="bar-4"></div>
                    </div>
                </div>

                <!-- Confirmar -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Confirmar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="bi bi-check2-circle text-gray-500"></i></div>
                        <input type="password" name="password_confirm" id="password_confirm" class="input-field w-full rounded-xl py-3 pl-11 pr-12 text-white placeholder-gray-600" placeholder="Repite tu contraseña" required autocomplete="new-password">
                    </div>
                </div>

                <!-- Botón -->
                <button type="submit" id="submit-btn" class="w-full group relative py-3.5 px-4 bg-gradient-to-r from-sj-purple to-indigo-600 hover:from-sj-purple hover:to-purple-500 text-white font-bold rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(139,92,246,0.3)] hover:shadow-[0_0_30px_rgba(139,92,246,0.5)] hover:-translate-y-0.5 mt-6 flex justify-center items-center overflow-hidden">
                    <div class="absolute top-0 left-[-100%] w-1/2 h-full bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-12 group-hover:left-[200%] transition-all duration-700"></div>
                    <span class="btn-text">Comenzar Aventura</span>
                    <div class="btn-spinner hidden">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>

            </form>

            <div class="mt-6 text-center border-t border-white/10 pt-6">
                <p class="text-sm text-gray-400">
                    ¿Ya tienes cuenta? 
                    <a href="login.php" class="text-sj-green font-bold hover:underline ml-1">Entrar</a>
                </p>
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-600 mt-8 font-mono">SurveyJunior Quantum v11.0</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('register-form');
            const btn = document.getElementById('submit-btn');
            const btnText = btn.querySelector('.btn-text');
            const btnSpinner = btn.querySelector('.btn-spinner');
            const alertBox = document.getElementById('alert-box');
            const alertMsg = document.getElementById('alert-msg');
            const alertIcon = alertBox.querySelector('i');
            const passInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirm');
            const bars = [document.getElementById('bar-1'), document.getElementById('bar-2'), document.getElementById('bar-3'), document.getElementById('bar-4')];

            document.querySelectorAll('.toggle-password').forEach(t => {
                t.addEventListener('click', () => {
                    const inp = document.getElementById(t.dataset.target);
                    const type = inp.type === 'password' ? 'text' : 'password';
                    inp.type = type;
                    t.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
                });
            });

            passInput.addEventListener('input', () => {
                const val = passInput.value;
                let strength = 0;
                if (val.length >= 6) strength++;
                if (val.match(/[A-Z]/)) strength++;
                if (val.match(/[0-9]/)) strength++;
                if (val.match(/[^a-zA-Z0-9]/)) strength++;
                bars.forEach((bar, i) => {
                    bar.className = 'strength-bar';
                    if (i < strength) {
                        if (strength <= 1) bar.classList.add('weak', 'active');
                        else if (strength <= 2) bar.classList.add('medium', 'active');
                        else bar.classList.add('strong', 'active');
                    }
                });
            });
            
            confirmInput.addEventListener('input', () => {
                if (confirmInput.value && confirmInput.value !== passInput.value) confirmInput.style.borderColor = '#F47174';
                else confirmInput.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (passInput.value !== confirmInput.value) { showAlert('Las contraseñas no coinciden.', 'danger'); return; }
                
                btn.disabled = true; 
                btnText.classList.add('hidden'); 
                btnSpinner.classList.remove('hidden'); 
                alertBox.classList.add('hidden');

                try {
                    const response = await fetch('register.php', { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, 
                        body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
                    });
                    const result = await response.json();
                    if (result.success) { 
                        showAlert(result.message, 'success'); 
                        setTimeout(() => window.location.href = result.redirect, 1500); 
                    } 
                    else throw new Error(result.message);
                } catch (error) {
                    showAlert(error.message, 'danger');
                    const card = document.querySelector('.glass-card');
                    card.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-5px)' }, { transform: 'translateX(5px)' }, { transform: 'translateX(0)' }], { duration: 400 });
                    btn.disabled = false; btnText.classList.remove('hidden'); btnSpinner.classList.add('hidden');
                }
            });

            function showAlert(msg, type) {
                alertBox.classList.remove('hidden', 'bg-red-500/10', 'border-red-500/20', 'text-red-200', 'bg-green-500/10', 'border-green-500/20', 'text-green-200');
                alertIcon.className = 'bi mt-0.5 text-lg';
                if (type === 'danger') { alertBox.classList.add('bg-red-500/10', 'border', 'border-red-500/20', 'text-red-200'); alertIcon.classList.add('bi-exclamation-triangle-fill', 'text-red-400'); } 
                else { alertBox.classList.add('bg-green-500/10', 'border', 'border-green-500/20', 'text-green-200'); alertIcon.classList.add('bi-check-circle-fill', 'text-green-400'); }
                alertMsg.innerHTML = msg; alertBox.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>