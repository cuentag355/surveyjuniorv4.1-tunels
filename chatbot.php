<?php
// modules/chatbot.php (Quantum Design)
if (!isset($view_data)) return;
$user = $view_data['user'];
?>

<div class="max-w-4xl mx-auto h-[calc(100vh-140px)] flex flex-col animate-fade-in">
    
    <div class="glass-panel rounded-t-2xl p-4 flex items-center justify-between border-b border-white/5 shrink-0">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-sj-blue to-sj-purple flex items-center justify-center shadow-lg shadow-sj-blue/20">
                <i class="bi bi-robot text-white text-lg"></i>
            </div>
            <div>
                <h2 class="font-bold text-white">Asistente IA</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-sj-green rounded-full animate-pulse"></span>
                    <span class="text-xs text-sj-green font-medium">En línea (Gemini)</span>
                </div>
            </div>
        </div>
        <div class="text-xs text-gray-500 bg-white/5 px-3 py-1 rounded-full">Exclusivo PRO</div>
    </div>

    <div id="chat-messages" class="flex-1 glass-panel border-y-0 bg-black/20 overflow-y-auto p-6 space-y-6 custom-scrollbar">
        <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sj-blue to-sj-purple flex items-center justify-center shrink-0 mt-1">
                <i class="bi bi-robot text-xs text-white"></i>
            </div>
            <div class="bg-white/10 text-gray-200 p-4 rounded-2xl rounded-tl-none max-w-[80%] border border-white/5 shadow-sm">
                <p>¡Hola <strong><?= htmlspecialchars($user['username']) ?></strong>! Soy tu copiloto cuántico. 🌌</p>
                <p class="mt-2 text-sm text-gray-400">Puedo ayudarte a traducir textos, redactar correos o responder dudas sobre encuestas.</p>
            </div>
        </div>
    </div>

    <div class="glass-panel rounded-b-2xl p-4 border-t border-white/5 shrink-0">
        <form id="chat-form" class="relative flex gap-3">
            <input type="text" id="chat-input" 
                   class="w-full bg-sj-dark/50 border border-white/10 rounded-xl pl-4 pr-12 py-3 text-white placeholder-gray-500 focus:border-sj-blue focus:ring-1 focus:ring-sj-blue transition-all" 
                   placeholder="Escribe tu mensaje aquí..." autocomplete="off" required>
            
            <button type="submit" id="chat-submit-btn" 
                    class="absolute right-2 top-2 bottom-2 px-4 bg-sj-blue hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="btn-text"><i class="bi bi-send-fill"></i></span>
                <span class="spinner-border w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin hidden"></span>
            </button>
        </form>
    </div>
</div>