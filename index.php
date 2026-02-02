<?php
/**
 * TV One Portal - Página Principal (v7.0)
 * Removido: Campo "Exibe" abaixo da sinopse
 */

require_once __DIR__ . '/config/functions.php';

$config = loadConfig();
$schedule = $config['schedule'] ?? [];

// Rastrear acesso
trackAccess();

// Ordenar a programação por horário (HH:MM)
usort($schedule, function($a, $b) {
    $timeA = $a['time'] ?? '99:99';
    $timeB = $b['time'] ?? '99:99';
    return strcmp($timeA, $timeB);
});

// Lógica para garantir que o carrossel tenha itens suficientes para o loop infinito
$displayItems = $schedule;
if (!empty($schedule)) {
    while (count($displayItems) < 10) {
        $displayItems = array_merge($displayItems, $schedule);
    }
    $displayItems = array_merge($displayItems, $displayItems);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($config['site_description']); ?>">
    
    <title><?php echo htmlspecialchars($config['site_title']); ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    
    <style>
        :root {
            --kick-green: <?php echo htmlspecialchars($config['theme_primary_color']); ?>;
            --dailymotion-blue: #0066DC;
            --bg-primary: <?php echo htmlspecialchars($config['theme_background']); ?>;
            --bg-secondary: <?php echo htmlspecialchars($config['theme_sidebar']); ?>;
        }

        /* Estilos para o Player Dailymotion */
        .dailymotion-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
            overflow: hidden;
        }

        .dailymotion-lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            background: transparent;
            cursor: default;
        }

        /* Ajuste para a sinopse manual */
        #movie-overview {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.5;
            max-height: 4.5em;
            color: #adadb8;
            font-size: 14px;
        }

        #movie-title-display {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .title-year {
            font-size: 0.6em;
            color: var(--kick-green);
            font-weight: 400;
            opacity: 0.8;
        }

        .ai-badge-container {
            margin-bottom: 10px;
        }

        /* Cursor da IA */
        .ia-cursor {
            display: inline-block;
            width: 2px;
            height: 1.2em;
            background-color: var(--kick-green);
            margin-left: 2px;
            vertical-align: middle;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Estilo para a logo como link */
        .logo-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }
        .logo-link:hover {
            transform: scale(1.02);
        }
        .main-logo {
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--kick-green);
        }

        /* Estilo para o horário no carrossel */
        .item-time {
            font-size: 10px;
            color: var(--kick-green);
            font-weight: 800;
            margin-top: 2px;
        }

        /* Ocultar elementos indesejados do iframe da Kick */
        .kick-player-container {
            position: relative;
            overflow: hidden;
        }
        
        /* Estilos para Fullscreen Automático (Landscape Mobile) */
        body.landscape-fullscreen {
            overflow: hidden !important;
        }

        body.landscape-fullscreen .premium-header,
        body.landscape-fullscreen .schedule-carousel-container,
        body.landscape-fullscreen .sidebar,
        body.landscape-fullscreen .movie-info-card,
        body.landscape-fullscreen .next-movies-section,
        body.landscape-fullscreen .card-header {
            display: none !important;
        }

        body.landscape-fullscreen .main-wrapper {
            height: 100vh !important;
            width: 100vw !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        body.landscape-fullscreen .main-content {
            width: 100vw !important;
            height: 100vh !important;
            padding: 0 !important;
        }

        body.landscape-fullscreen .kick-card {
            width: 100vw !important;
            height: 100vh !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        body.landscape-fullscreen .kick-player-container {
            width: 100vw !important;
            height: 100vh !important;
        }

        body.landscape-fullscreen #kick-player,
        body.landscape-fullscreen #kick-iframe {
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
        }

        /* Ocultar elementos internos da Kick via técnica de overflow e escala no Fullscreen */
        body.landscape-fullscreen .kick-player-container {
            width: 100vw !important;
            height: 100vh !important;
            overflow: hidden !important;
        }

        /* Ajuste para o modo fullscreen mobile */
        body.landscape-fullscreen #kick-iframe {
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            pointer-events: none !important;
        }

        /* ============================================
           BLOQUEIO DE INTERAÇÃO E LOADING OVERLAY
           ============================================ */

        /* Overlay para bloquear interações na Kick */
        .kick-interaction-blocker {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 15;
            background: transparent;
            cursor: default;
            pointer-events: all;
        }

        /* Overlay de carregamento */
        .kick-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 20;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .loading-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        /* Spinner com múltiplos anéis */
        .loading-spinner {
            position: relative;
            width: 80px;
            height: 80px;
        }

        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
        }

        .spinner-ring:nth-child(1) {
            border-top-color: var(--kick-green);
            animation: spin 1.2s linear infinite;
        }

        .spinner-ring:nth-child(2) {
            width: 70%;
            height: 70%;
            top: 15%;
            left: 15%;
            border-right-color: var(--kick-green);
            animation: spin 1.5s linear infinite reverse;
        }

        .spinner-ring:nth-child(3) {
            width: 40%;
            height: 40%;
            top: 30%;
            left: 30%;
            border-bottom-color: var(--kick-green);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Texto de carregamento com efeito pulsante */
        .loading-text {
            font-size: 18px;
            font-weight: 900;
            color: var(--kick-green);
            letter-spacing: 4px;
            text-transform: uppercase;
            animation: pulse-text 1.5s ease-in-out infinite;
        }

        @keyframes pulse-text {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.98); }
        }

        /* Dots animados */
        .loading-dots {
            display: flex;
            gap: 8px;
        }

        .loading-dots span {
            width: 10px;
            height: 10px;
            background: var(--kick-green);
            border-radius: 50%;
            animation: dot-bounce 1.4s ease-in-out infinite;
        }

        .loading-dots span:nth-child(1) { animation-delay: 0s; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes dot-bounce {
            0%, 80%, 100% {
                transform: scale(0.6);
                opacity: 0.4;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .loading-subtitle {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
            margin-top: 10px;
        }

        /* Timer de reconexão */
        .loading-timer {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
        }

        .loading-timer span {
            color: var(--kick-green);
            font-weight: 700;
        }

        /* Animação de fade out */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Esconder loading em fullscreen mode */
        body.landscape-fullscreen .kick-loading-overlay,
        body.css-fullscreen-active .kick-loading-overlay {
            z-index: 9999;
        }
    </style>
</head>
<body data-kick="<?php echo htmlspecialchars($config['kick_channel']); ?>">
    <!-- Header Premium -->
    <header class="premium-header">
        <div class="header-left">
            <a href="index.php" class="logo-link" title="Atualizar Portal">
                <img src="https://files.kick.com/images/user/20811005/profile_image/conversion/04a5fbcf-47c7-4ce3-8782-10a1a69fc169-medium.webp" alt="Logo" class="main-logo">
                <div class="brand-info">
                    <h1 class="brand-title"><?php echo htmlspecialchars($config['site_title']); ?></h1>
                </div>
            </a>
            <a href="javascript:void(0)" onclick="initVotingPopup()" class="mobile-schedule-link" title="Votar no Próximo Filme">Escolha 1 Filme</a>
        </div>
        <div class="header-right">
            <a href="javascript:void(0)" onclick="initVotingPopup()" class="desktop-schedule-link">Escolha 1 Filme</a>
            <div class="description-box">
                <span class="desc-text"><?php echo htmlspecialchars($config['site_description']); ?></span>
            </div>
        </div>
    </header>

    <div class="main-wrapper">
        <!-- 1. Sidebar de Programação -->
        <?php if (!empty($schedule)): ?>
        <div class="schedule-carousel-container">
            <div class="schedule-label">
                <?php 
                $fullTitle = $config['schedule_title'] ?? 'Programação';
                $parts = explode(' ', $fullTitle, 2);
                if (count($parts) > 1) {
                    echo '<span class="title-main">' . htmlspecialchars($parts[0]) . '</span><br>';
                    echo '<span class="title-sub">' . htmlspecialchars($parts[1]) . '</span>';
                } else {
                    echo '<span class="title-main">' . htmlspecialchars($fullTitle) . '</span>';
                }
                ?>
            </div>
            <div class="carousel-viewport">
                <div class="carousel-track" id="carousel-track">
                    <?php foreach ($displayItems as $item): ?>
                    <div class="carousel-item" 
                         data-title="<?php echo htmlspecialchars($item['title']); ?>" 
                         data-year="<?php echo htmlspecialchars($item['year'] ?? ''); ?>" 
                         data-time="<?php echo htmlspecialchars($item['time'] ?? ''); ?>"
                         data-synopsis="<?php echo htmlspecialchars($item['synopsis'] ?? ''); ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Avatar">
                        <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <?php if (!empty($item['time'])): ?>
                            <div class="item-time"><?php echo htmlspecialchars($item['time']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. Conteúdo Central (Kick) -->
        <main class="main-content">
            <div class="player-card kick-card">
                <div class="kick-player-container">
                    <!-- Overlay de bloqueio de interação -->
                    <div class="kick-interaction-blocker" id="kick-blocker"></div>

                    <!-- Overlay de carregamento -->
                    <div class="kick-loading-overlay" id="kick-loading" style="display: none;">
                        <div class="loading-content">
                            <div class="loading-spinner">
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                            </div>
                            <div class="loading-text">CARREGANDO</div>
                            <div class="loading-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <p class="loading-subtitle">Aguarde, a transmissão está sendo reconectada...</p>
                        </div>
                    </div>

                    <!-- Overlay de dica fullscreen mobile -->
                    <div class="fullscreen-hint-overlay" id="fullscreen-hint">
                        <div class="fullscreen-hint-content">
                            <svg class="rotate-phone-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="18" y="8" width="28" height="48" rx="4" stroke="currentColor" stroke-width="3"/>
                                <circle cx="32" cy="50" r="2" fill="currentColor"/>
                                <path d="M50 32 L58 32 M58 32 L54 28 M58 32 L54 36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <p class="fullscreen-hint-text">Deite o celular para tela cheia</p>
                            <button class="fullscreen-hint-btn" id="fullscreen-hint-btn">ENTENDI</button>
                        </div>
                    </div>

                    <!-- Botão de Pause -->
                    <button class="kick-pause-btn" id="kick-pause-btn" title="Pausar transmissão">
                        <svg class="pause-icon" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="6" y="4" width="4" height="16" rx="1"/>
                            <rect x="14" y="4" width="4" height="16" rx="1"/>
                        </svg>
                        <svg class="play-icon" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>

                    <div id="kick-player"></div>
                </div>
            </div>

            <!-- Box de Sinopse -->
            <div class="movie-info-card" id="movie-info-box" style="display: none;">
                <div class="info-layout">
                    <div class="info-left">
                        <div class="ai-badge-container">
                            <span class="ai-badge-top">Sinopse do Filme</span>
                            <span class="ai-badge">IA</span>
                        </div>
                        <h2 id="movie-title-display">
                            <span id="movie-name">Carregando...</span>
                            <span id="movie-year-val" class="title-year"></span>
                        </h2>
                    </div>
                    <div class="info-right">
                        <div class="info-body">
                            <p id="movie-overview"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel de Próximos Filmes -->
            <div class="next-movies-section" id="next-movies-section" style="display: none;">
                <div class="next-movies-header">
                    <h3>Próximos Filmes</h3>
                </div>
                <div class="next-movies-carousel" id="next-movies-carousel">
                    <!-- Preenchido por JavaScript -->
                </div>
            </div>
        </main>

        <!-- 3. Sidebar Direita (Dailymotion + Chat) -->
        <aside class="sidebar">
            <div class="player-card dailymotion-card">
                <div class="card-header">
                    <span class="card-dot" style="background: var(--dailymotion-blue);"></span> Dailymotion Monitor
                </div>
                <div class="dailymotion-box-top" id="dailymotion-player-container">
                    <div class="dailymotion-wrapper">
                        <div class="dailymotion-lock-overlay"></div>
                        <div id="dailymotion-player-target" style="width:100%; height:100%;"></div>
                    </div>
                </div>
            </div>

            <div class="player-card chat-card">
                <div class="card-header">
                    <span class="card-dot"></span> Chat da Transmissão
                </div>
                <div class="chat-container">
                    <iframe id="chat-frame" src="https://kick.com/<?php echo htmlspecialchars($config['kick_channel']); ?>/chatroom" style="width:100%; height:100%; border:none;"></iframe>
                </div>
            </div>
        </aside>
    </div>

    <!-- Scripts -->
    <script src="assets/js/main.js?v=<?php echo filemtime(__DIR__ . '/assets/js/main.js'); ?>"></script>
    
    <script>
        // Dados de programação
        const scheduleData = <?php echo json_encode($schedule); ?>;
        
        // Função showMovieInfo simplificada para ser chamada pelo JS principal se necessário
        function showMovieInfo(index) {
            const item = scheduleData[index];
            if (!item) return;
            
            // O título e ano são atualizados pelo loop da IA no main.js
            // Esta função pode ser usada para outras atualizações de interface se necessário
            console.log("Interface atualizada para o filme:", item.title);
        }
        
        // Preencher carousel de próximos filmes
        function loadNextMovies() {
            const carousel = document.getElementById('next-movies-carousel');
            const section = document.getElementById('next-movies-section');
            
            if (scheduleData.length > 0) {
                // Ordenar a programação por horário (HH:MM)
                const sortedSchedule = [...scheduleData].sort((a, b) => {
                    if (!a.time) return 1;
                    if (!b.time) return -1;
                    return a.time.localeCompare(b.time);
                });

                // Encontrar o índice do filme atual na lista ordenada para mostrar os próximos
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const currentTime = `${h}:${m}`;
                
                // Filtrar apenas filmes cujo horário é estritamente maior que o atual (o próximo a passar)
                const nextMovies = sortedSchedule.filter(item => item.time > currentTime);
                
                // Se não houver próximos hoje (final do dia), mostra o início da lista (programação de amanhã)
                // Mas se houver próximos, também adicionamos o início da lista depois deles para completar o carrossel
                const displayList = [...nextMovies, ...sortedSchedule];

                section.style.display = 'block';
                carousel.innerHTML = '';
                
                // Mostrar os próximos filmes (limitado a 4 capas, sem scroll)
                displayList.slice(0, 4).forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'next-movie-item';
                    div.innerHTML = `
                        <img src="${item.image}" alt="${item.title}">
                        <div class="next-movie-info">
                            <div class="next-movie-title">${item.title}</div>
                            <div class="next-movie-year">${item.year || ''}</div>
                            ${item.time ? `<div class="next-movie-year" style="color: var(--kick-green); font-weight: 800;">${item.time}</div>` : ''}
                        </div>
                    `;
                    carousel.appendChild(div);
                });
            }
        }
        
        // Carregar ao iniciar
        loadNextMovies();
        
    </script>
    
    <!-- Popup de Votação Flutuante -->
    <div id="voting-popup" class="voting-popup" style="display: none;">
        <div class="voting-popup-overlay"></div>
        <div class="voting-popup-content">
            <h2 class="voting-popup-title">🗳️ Escolha 1 Filme</h2>
            <p class="voting-popup-subtitle">Escolha seu favorito e ajude a definir a programação!</p>
            
            <div class="voting-popup-grid" id="voting-popup-grid">
                <!-- Preenchido por JavaScript -->
            </div>
            
            <button class="voting-popup-skip-btn" onclick="skipVoting()" title="Fechar sem votar">
                Não Votar Agora
            </button>
        </div>
    </div>
    
    <style>
        .voting-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .voting-popup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }
        
        .voting-popup-content {
            position: relative;
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid rgba(83, 252, 24, 0.2);
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(83, 252, 24, 0.1);
            animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .voting-popup-title {
            font-size: 24px;
            font-weight: 900;
            color: #fff;
            text-align: center;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .voting-popup-subtitle {
            font-size: 13px;
            color: #adadb8;
            text-align: center;
            margin: 0 0 25px 0;
        }
        
        .voting-popup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .voting-popup-card {
            background: rgba(26, 26, 26, 0.8);
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .voting-popup-card:hover {
            border-color: var(--kick-green);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(83, 252, 24, 0.2);
        }
        
        .voting-popup-card.voted {
            border-color: var(--kick-green);
            box-shadow: 0 0 20px rgba(83, 252, 24, 0.3);
        }
        
        .voting-popup-poster-wrapper {
            width: 100%;
            aspect-ratio: 2/3;
            overflow: hidden;
            position: relative;
        }
        
        .voting-popup-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .voting-popup-overlay-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.95), transparent);
        }
        
        .voting-popup-movie-title {
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            text-align: center;
            text-transform: uppercase;
        }
        
        .voting-popup-progress {
            background: rgba(255, 255, 255, 0.1);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .voting-popup-progress-bar {
            height: 100%;
            background: var(--kick-green);
            transition: width 0.8s ease;
        }
        
        .voting-popup-percentage {
            font-size: 18px;
            font-weight: 900;
            color: var(--kick-green);
            text-align: center;
        }
        
        .voting-popup-skip-btn {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .voting-popup-skip-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        .voting-popup-skip-btn:active {
            transform: translateY(0);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @media (max-width: 768px) {
            .voting-popup-content {
                padding: 20px;
                max-width: 95%;
            }
            
            .voting-popup-title {
                font-size: 18px;
            }
            
            .voting-popup-subtitle {
                font-size: 11px;
            }
            
            .voting-popup-movie-title {
                font-size: 11px;
            }
            
            .voting-popup-percentage {
                font-size: 16px;
            }
        }
    </style>
    
    <script>
        // Sistema de Popup de Votação
        function initVotingPopup() {
            // Verifica se há votação ativa
            fetch('get_voting_data.php')
                .then(response => response.json())
                .then(data => {
                    // Agora sempre aparece se estiver ativo na administração
                    if (data.has_voting) {
                        showVotingPopup(data.voting, data.has_voted, data.voted_for);
                    }
                })
                .catch(err => console.error('Erro ao carregar dados de votação:', err));
        }
        
        function showVotingPopup(voting, hasVoted, votedFor) {
            const popup = document.getElementById('voting-popup');
            const grid = document.getElementById('voting-popup-grid');
            const subtitle = document.querySelector('.voting-popup-subtitle');
            
            if (hasVoted) {
                const votedTitle = votedFor === 'movie1' ? voting.movie1.title : (votedFor === 'movie2' ? voting.movie2.title : 'já registrado');
                subtitle.innerHTML = `<span style="color: var(--kick-green); font-weight: 800;">VOCÊ JÁ VOTOU EM: ${votedTitle}</span>`;
            } else {
                subtitle.textContent = voting.voting_title || 'ESCOLHA O FILME QUE VOCÊ QUER VER AMANHÃ';
            }
            
            const total = voting.movie1.votes + voting.movie2.votes;
            const perc1 = total > 0 ? Math.round((voting.movie1.votes / total) * 100) : 50;
            const perc2 = total > 0 ? Math.round((voting.movie2.votes / total) * 100) : 50;
            
            const card1Class = (hasVoted && votedFor === 'movie1') ? 'voting-popup-card voted' : 'voting-popup-card';
            const card2Class = (hasVoted && votedFor === 'movie2') ? 'voting-popup-card voted' : 'voting-popup-card';
            const click1 = hasVoted ? '' : 'onclick="castVotePopup(\'movie1\')"';
            const click2 = hasVoted ? '' : 'onclick="castVotePopup(\'movie2\')"';
            
            grid.innerHTML = `
                <div class="${card1Class}" ${click1} style="${hasVoted ? 'cursor: default' : ''}">
                    <div class="voting-popup-poster-wrapper">
                        <img src="${voting.movie1.image}" class="voting-popup-poster" alt="${voting.movie1.title}">
                        <div class="voting-popup-overlay-info">
                            <div class="voting-popup-movie-title">${voting.movie1.title}</div>
                            <div class="voting-popup-progress">
                                <div class="voting-popup-progress-bar" id="popup-bar-movie1" style="width: ${perc1}%"></div>
                            </div>
                            <div class="voting-popup-percentage" id="popup-perc-movie1">${perc1}%</div>
                        </div>
                    </div>
                </div>
                <div class="${card2Class}" ${click2} style="${hasVoted ? 'cursor: default' : ''}">
                    <div class="voting-popup-poster-wrapper">
                        <img src="${voting.movie2.image}" class="voting-popup-poster" alt="${voting.movie2.title}">
                        <div class="voting-popup-overlay-info">
                            <div class="voting-popup-movie-title">${voting.movie2.title}</div>
                            <div class="voting-popup-progress">
                                <div class="voting-popup-progress-bar" id="popup-bar-movie2" style="width: ${perc2}%"></div>
                            </div>
                            <div class="voting-popup-percentage" id="popup-perc-movie2">${perc2}%</div>
                        </div>
                    </div>
                </div>
            `;
            
            popup.style.display = 'flex';
        }
        
        function castVotePopup(option) {
            const formData = new FormData();
            formData.append('vote_option', option);
            
            fetch('programacao.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePopupBars(data.votes);
                    
                    // Atualiza o subtítulo para mostrar em quem votou
                    const subtitle = document.querySelector('.voting-popup-subtitle');
                    const movieTitle = option === 'movie1' ? 
                        document.querySelector('#voting-popup-grid .voting-popup-card:first-child .voting-popup-movie-title').textContent :
                        document.querySelector('#voting-popup-grid .voting-popup-card:last-child .voting-popup-movie-title').textContent;
                    
                    subtitle.innerHTML = `<span style="color: var(--kick-green); font-weight: 800;">VOTO REGISTRADO: ${movieTitle}</span>`;

                    document.querySelectorAll('.voting-popup-card').forEach(c => {
                        c.classList.add('voted');
                        c.style.cursor = 'default';
                        c.onclick = null;
                    });
                    
                    // Fecha o popup após 2 segundos
                    setTimeout(() => {
                        closeVotingPopup();
                    }, 2000);
                }
            })
            .catch(err => console.error('Erro ao votar:', err));
        }
        
        function updatePopupBars(votes) {
            const v1 = parseInt(votes.movie1.votes);
            const v2 = parseInt(votes.movie2.votes);
            const total = v1 + v2;
            const p1 = total > 0 ? Math.round((v1 / total) * 100) : 50;
            const p2 = total > 0 ? Math.round((v2 / total) * 100) : 50;
            
            const bar1 = document.getElementById('popup-bar-movie1');
            const bar2 = document.getElementById('popup-bar-movie2');
            const perc1 = document.getElementById('popup-perc-movie1');
            const perc2 = document.getElementById('popup-perc-movie2');
            
            if (bar1) bar1.style.width = p1 + '%';
            if (bar2) bar2.style.width = p2 + '%';
            if (perc1) perc1.textContent = p1 + '%';
            if (perc2) perc2.textContent = p2 + '%';
        }
        
        function closeVotingPopup() {
            const popup = document.getElementById('voting-popup');
            popup.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                popup.style.display = 'none';
            }, 300);
        }
        
        function skipVoting() {
            // Fecha o popup sem registrar voto
            closeVotingPopup();
            
            // Removido o bloqueio por localStorage para que a janela 
            // apareça sempre que o site for aberto, conforme solicitado.
        }
    </script>
</body>
</html>
