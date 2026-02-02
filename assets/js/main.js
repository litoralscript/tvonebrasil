/**
 * TV One Portal - Script Principal (v4.0 - Kick Blocker + Auto Reload)
 * Atualizado: Bloqueio de interação, detecção de queda e auto-reload do iframe Kick
 */

const CONFIG = {
    kickChannel: document.body.dataset.kick || 'tvonebrasil',
    dailymotionVideoId: 'x9ybb3k',
    logoUrl: 'https://files.kick.com/images/user/20811005/profile_image/conversion/04a5fbcf-47c7-4ce3-8782-10a1a69fc169-medium.webp'
};

let currentMovieIndex = 0;
let isTypewriterRunning = false;
let kickMonitorInterval = null;
let kickReloadTimeout = null;
let isKickLoading = false;

document.addEventListener("DOMContentLoaded", () => {
    console.log("Portal TV One Iniciado - V4.0 (Kick Blocker)");
    initPlayers();
    initWelcomeOverlay();

    // Inicializa o índice baseado no horário atual
    updateCurrentMovieIndex();

    setTimeout(initIASimulation, 1500);
    setTimeout(sendDailymotionCommands, 2000);

    // Iniciar monitoramento do Kick após 5 segundos
    setTimeout(initKickMonitor, 5000);

    // Verificar a cada 30 segundos se o filme mudou para garantir precisão
    setInterval(() => {
        const oldIndex = currentMovieIndex;
        updateCurrentMovieIndex();
        if (oldIndex !== currentMovieIndex) {
            console.log("Filme mudou! Atualizando interface...");
            // Mostrar loading e recarregar iframe da Kick
            showKickLoading();
            setTimeout(reloadKickIframe, 2000);
            if (typeof showMovieInfo === 'function') {
                showMovieInfo(currentMovieIndex);
            }
        }
    }, 30000);
});

function updateCurrentMovieIndex() {
    if (typeof scheduleData === 'undefined' || scheduleData.length === 0) return;

    const now = new Date();
    const currentTime = now.getHours() * 60 + now.getMinutes();
    
    const sortedSchedule = [...scheduleData].sort((a, b) => {
        const timeA = a.time || '00:00';
        const timeB = b.time || '00:00';
        return timeA.localeCompare(timeB);
    });

    let foundIndex = 0;
    let foundInSorted = false;

    for (let i = sortedSchedule.length - 1; i >= 0; i--) {
        const item = sortedSchedule[i];
        if (item.time) {
            const [hours, minutes] = item.time.split(':').map(Number);
            const itemTime = hours * 60 + minutes;
            
            if (currentTime >= itemTime) {
                const originalIndex = scheduleData.findIndex(orig => orig.title === item.title && orig.time === item.time);
                foundIndex = originalIndex !== -1 ? originalIndex : 0;
                foundInSorted = true;
                break;
            }
        }
    }

    if (!foundInSorted) {
        const lastItem = sortedSchedule[sortedSchedule.length - 1];
        const originalIndex = scheduleData.findIndex(orig => orig.title === lastItem.title && orig.time === lastItem.time);
        foundIndex = originalIndex !== -1 ? originalIndex : 0;
    }

    currentMovieIndex = foundIndex;
}

function initPlayers() {
    const kickContainer = document.getElementById("kick-player");
    if (kickContainer) {
        kickContainer.innerHTML = `<div style="width:100%; height:100%; background:#000; display:flex; align-items:center; justify-content:center; color:var(--kick-green); font-weight:800; font-size:12px; letter-spacing:2px;">CARREGANDO PLAYER...</div>`;
    }

    const dailymotionTarget = document.getElementById("dailymotion-player-target");
    if (dailymotionTarget) {
        dailymotionTarget.innerHTML = `
            <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">
                <iframe id="dailymotion-iframe" 
                        src="https://geo.dailymotion.com/player.html?video=${CONFIG.dailymotionVideoId}&autoplay=1&mute=1&volume=0&controls=false&ui-start-screen-info=false&ui-logo=false" 
                        style="width:100%; height:100%; position:absolute; left:0px; top:0px; overflow:hidden; border:none; pointer-events:none;" 
                        title="Dailymotion Video Player" 
                        sandbox="allow-scripts allow-same-origin"
                        allow="">
                </iframe>
            </div>`;
    }
}

function initWelcomeOverlay() {
    const existing = document.querySelector(".unmute-overlay-agressive");
    if (existing) existing.remove();

    const overlay = document.createElement("div");
    overlay.className = "unmute-overlay-agressive";
    overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:#09090b;z-index:999999;display:flex;align-items:center;justify-content:center;cursor:pointer;transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1);";
    
    overlay.innerHTML = `
        <div class="welcome-content" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; width:90%; max-width:400px; padding:30px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; backdrop-filter: blur(10px);">
            <div class="welcome-logo-wrapper" style="margin-bottom:30px; position:relative;">
                <div class="logo-glow" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:120px; height:120px; background:var(--kick-green); border-radius:50%; filter:blur(30px); opacity:0.2; animation: pulseGlow 3s infinite alternate;"></div>
                <img src="${CONFIG.logoUrl}" style="width:100px; height:100px; border-radius:50%; border:2px solid var(--kick-green); position:relative; z-index:2; object-fit:cover; animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);">
            </div>
            <h1 style="color:#fff; font-size:24px; font-weight:900; margin:0 0 10px 0; letter-spacing:2px; text-transform:uppercase; white-space: nowrap; animation: fadeInUp 0.8s ease-out 0.2s both;">BEM-VINDO</h1>
            <p style="color:rgba(255,255,255,0.6); font-size:12px; margin-bottom:30px; animation: fadeInUp 0.8s ease-out 0.4s both;">O melhor do entretenimento premium</p>
            <button class="welcome-button" style="background:var(--kick-green); color:#000; width:100%; padding:16px; border-radius:12px; font-size:14px; font-weight:800; border:none; cursor:pointer; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s ease; animation: fadeInUp 0.8s ease-out 0.6s both;">
                ENTRAR NO PORTAL
            </button>
        </div>
        <style>
            @keyframes pulseGlow { from { opacity: 0.1; transform: translate(-50%, -50%) scale(0.9); } to { opacity: 0.25; transform: translate(-50%, -50%) scale(1.1); } }
            @keyframes scaleIn { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            .welcome-button:hover { background: #fff; color: #000; border-color: #fff; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        </style>
    `;

    const enterPortal = (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Marca interação ANTES de tentar fullscreen
        if (window.fullscreenState) window.fullscreenState.userHasInteracted = true;

        // Ativa fullscreen no celular ao entrar no portal
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        if (isMobile) {
            requestFullscreenOnMobile();
        }

        const kickContainer = document.getElementById("kick-player");
        if (kickContainer) {
            kickContainer.innerHTML = `<iframe id="kick-iframe" src="https://player.kick.com/${CONFIG.kickChannel}?autoplay=true&muted=false&volume=1&ui-logo=false&ui-info=false" frameborder="0" scrolling="no" allowfullscreen="true" allow="autoplay; encrypted-media" style="width: 100%; height: 100%; border: none;"></iframe>`;
        }
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const audioCtx = new AudioContext();
            if (audioCtx.state === 'suspended') audioCtx.resume();
        } catch (err) {}
        overlay.style.opacity = "0";
        setTimeout(() => {
            overlay.remove();
            if (typeof initVotingPopup === 'function') {
                initVotingPopup();
            }
        }, 600);
    };

    function requestFullscreenOnMobile() {
        const docElm = document.documentElement;
        try {
            if (docElm.requestFullscreen) {
                docElm.requestFullscreen().catch(e => console.warn("Erro FS:", e));
            } else if (docElm.webkitRequestFullscreen) {
                docElm.webkitRequestFullscreen();
            } else if (docElm.mozRequestFullScreen) {
                docElm.mozRequestFullScreen();
            } else if (docElm.msRequestFullscreen) {
                docElm.msRequestFullscreen();
            }
        } catch (err) {
            console.warn("Fullscreen não disponível:", err);
        }
    }

    overlay.addEventListener("click", enterPortal);
    document.body.appendChild(overlay);
    
    // Iniciar monitoramento de orientação e remoção de elementos da Kick
    initKickTweaks();
    initAutoFullscreen();
    
    // Listener adicional para garantir que ao girar o celular, a UI da Kick permaneça oculta
    window.addEventListener('resize', () => {
        const isLandscape = window.innerWidth > window.innerHeight;
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        if (isMobile && isLandscape) {
            document.body.classList.add('landscape-fullscreen');
        } else if (isMobile && !isLandscape) {
            // Só remove se não for fullscreen nativo
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                document.body.classList.remove('landscape-fullscreen');
            }
        }
    });
}

function initKickTweaks() {
    const style = document.createElement('style');
    style.innerHTML = `
        .kick-player-container {
            overflow: hidden !important;
            position: relative;
        }
        #kick-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            margin: 0;
            padding: 0;
        }
        body.landscape-fullscreen #kick-iframe {
            margin: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }
    `;
    document.head.appendChild(style);

    const kickContainer = document.querySelector('.kick-player-container');
    if (kickContainer) {
        // Monitorar se o iframe foi trocado
        const observer = new MutationObserver(() => {
            const iframe = document.getElementById('kick-iframe');
            if (iframe) {
                // Garantir estilos corretos
                iframe.style.margin = '0';
                iframe.style.padding = '0';
            }
        });
        observer.observe(kickContainer, { childList: true, subtree: true });
    }
}

function initAutoFullscreen() {
    window.fullscreenState = {
        userHasInteracted: false,
        lastOrientation: null,
        manualExit: false
    };

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    if (!isMobile) return;

    const markInteraction = () => {
        if (!window.fullscreenState.userHasInteracted) {
            window.fullscreenState.userHasInteracted = true;
            console.log('[Fullscreen] Interação detectada');
            // Ao interagir pela primeira vez, se já estiver em landscape, tenta FS
            setTimeout(checkOrientation, 100);
        }
    };
    document.addEventListener('click', markInteraction, { capture: true });
    document.addEventListener('touchstart', markInteraction, { capture: true });

    createMobileFullscreenButton();

    function createMobileFullscreenButton() {
        if (document.getElementById('mobile-fullscreen-btn')) return;
        const btn = document.createElement('button');
        btn.id = 'mobile-fullscreen-btn';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>`;
        btn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--kick-green, #53fc18);
            color: #000;
            border: none;
            box-shadow: 0 4px 20px rgba(83, 252, 24, 0.5);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.fullscreenState.manualExit = false; // Reset manual exit on button click
            applyCSSFullscreen();
            tryNativeFullscreen();
        });

        document.body.appendChild(btn);
    }

    function applyCSSFullscreen() {
        console.log('[Fullscreen] Ativando CSS Fullscreen');
        document.body.classList.add('landscape-fullscreen');
        document.body.classList.add('css-fullscreen-active');
        
        const btn = document.getElementById('mobile-fullscreen-btn');
        if (btn) btn.style.display = 'none';
        
        // Esconde barra de endereço se possível
        window.scrollTo(0, 1);
    }

    function removeCSSFullscreen() {
        console.log('[Fullscreen] Removendo CSS Fullscreen');
        document.body.classList.remove('landscape-fullscreen');
        document.body.classList.remove('css-fullscreen-active');

        const btn = document.getElementById('mobile-fullscreen-btn');
        if (btn) btn.style.display = 'flex';

        // Forçar um scroll para garantir que a interface se ajuste
        window.scrollTo(0, 0);

        // Garantir que o player volte ao tamanho original dentro do layout
        const kickIframe = document.getElementById('kick-iframe');
        if (kickIframe) {
            kickIframe.style.width = '100%';
            kickIframe.style.height = '100%';
            kickIframe.style.margin = '0';
            kickIframe.style.padding = '0';
        }
    }

    function tryNativeFullscreen() {
        try {
            const docElm = document.documentElement;
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (docElm.requestFullscreen) docElm.requestFullscreen().catch(() => {});
                else if (docElm.webkitRequestFullscreen) docElm.webkitRequestFullscreen();
            }
        } catch (e) {}
    }

    function checkOrientation() {
        const isLandscape = window.innerWidth > window.innerHeight;
        const currentOrientation = isLandscape ? 'landscape' : 'portrait';
        
        if (!window.fullscreenState.userHasInteracted) return;

        if (isLandscape) {
            // Se entrou em landscape e não foi uma saída manual recente
            if (!window.fullscreenState.manualExit) {
                applyCSSFullscreen();
                tryNativeFullscreen();
            }
        } else {
            // Portrait sempre remove e reseta o flag de saída manual
            removeCSSFullscreen();
            window.fullscreenState.manualExit = false;
        }

        window.fullscreenState.lastOrientation = currentOrientation;
    }

    // Eventos de mudança
    window.addEventListener('orientationchange', () => {
        setTimeout(checkOrientation, 200);
        setTimeout(checkOrientation, 500);
    });

    window.addEventListener('resize', () => {
        clearTimeout(window._fsResizeTimeout);
        window._fsResizeTimeout = setTimeout(checkOrientation, 200);
    });

    // Monitorar saída do Fullscreen Nativo
    const handleFullscreenExit = () => {
        const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
        if (!isFullscreen) {
            console.log('[Fullscreen] Saída do Fullscreen Nativo detectada');
            
            // Se ainda estiver em landscape, mas o FS nativo saiu, 
            // tratamos como "kick" ou saída manual do usuário
            const isLandscape = window.innerWidth > window.innerHeight;
            if (isLandscape) {
                window.fullscreenState.manualExit = true;
                removeCSSFullscreen();
            } else {
                window.fullscreenState.manualExit = false;
                removeCSSFullscreen();
            }
        }
    };

    document.addEventListener('fullscreenchange', handleFullscreenExit);
    document.addEventListener('webkitfullscreenchange', handleFullscreenExit);

    // Check inicial
    setTimeout(checkOrientation, 1000);
}

function sendDailymotionCommands() {
    const dailymotionIframe = document.getElementById("dailymotion-iframe");
    if (dailymotionIframe) {
        try {
            const commands = [{ command: 'muted', parameters: [true] }, { command: 'setVolume', parameters: [0] }];
            commands.forEach(cmd => { dailymotionIframe.contentWindow.postMessage(JSON.stringify(cmd), '*'); });
        } catch (err) {}
    }
}

function initIASimulation() {
    if (typeof scheduleData === 'undefined' || scheduleData.length === 0) return;
    startTypewriterLoop();
}

async function startTypewriterLoop() {
    const infoBox = document.getElementById('movie-info-box');
    const nameDisplay = document.getElementById('movie-name');
    const yearDisplay = document.getElementById('movie-year-val');
    const overviewDisplay = document.getElementById('movie-overview');

    const welcomeMsg = "Bem-vindo à TVONEBRASIL! O seu portal premium de entretenimento. Aproveite a melhor programação com qualidade máxima e interatividade total.";
    const typeSpeed = 40;
    const backSpeed = 20;
    const waitTime = 30000;

    if (infoBox) infoBox.style.display = 'block';
    let lastIndex = -1;

    while (true) {
        if (lastIndex !== currentMovieIndex) {
            const item = scheduleData[currentMovieIndex];
            if (nameDisplay) nameDisplay.innerText = item.title;
            if (yearDisplay) yearDisplay.innerText = item.year ? `(${item.year})` : '';
            lastIndex = currentMovieIndex;
        }

        const currentItem = scheduleData[currentMovieIndex];
        const synopsis = currentItem.synopsis || 'Sem sinopse disponível.';

        if (overviewDisplay) {
            await typeText(overviewDisplay, synopsis, typeSpeed);
            for(let i=0; i<waitTime/1000; i++) {
                await sleep(1000);
                if (lastIndex !== currentMovieIndex) break;
            }
            await deleteText(overviewDisplay, backSpeed);
            await sleep(500);
            
            if (lastIndex !== currentMovieIndex) continue;

            await typeText(overviewDisplay, welcomeMsg, typeSpeed);
            for(let i=0; i<waitTime/1000; i++) {
                await sleep(1000);
                if (lastIndex !== currentMovieIndex) break;
            }
            await deleteText(overviewDisplay, backSpeed);
            await sleep(500);
        } else {
            await sleep(1000);
        }
    }
}

function typeText(element, text, speed) {
    return new Promise(resolve => {
        let i = 0;
        element.innerHTML = '';
        const cursor = document.createElement('span');
        cursor.className = 'ia-cursor';
        element.appendChild(cursor);
        
        const timer = setInterval(() => {
            if (i < text.length) {
                const char = document.createTextNode(text.charAt(i));
                element.insertBefore(char, cursor);
                i++;
            } else {
                clearInterval(timer);
                resolve();
            }
        }, speed);
    });
}

function deleteText(element, speed) {
    return new Promise(resolve => {
        const timer = setInterval(() => {
            const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
            if (textNodes.length > 0) {
                const lastNode = textNodes[textNodes.length - 1];
                const val = lastNode.nodeValue;
                if (val.length > 1) {
                    lastNode.nodeValue = val.substring(0, val.length - 1);
                } else {
                    element.removeChild(lastNode);
                }
            } else {
                clearInterval(timer);
                resolve();
            }
        }, speed);
    });
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ============================================
// SISTEMA DE MONITORAMENTO E RELOAD DA KICK
// ============================================

/**
 * Inicializa o monitoramento do iframe da Kick
 */
function initKickMonitor() {
    console.log('[Kick Monitor] Iniciando monitoramento...');

    // Monitorar o iframe a cada 10 segundos
    kickMonitorInterval = setInterval(checkKickStatus, 10000);

    // Listener para detectar erros no iframe
    const kickIframe = document.getElementById('kick-iframe');
    if (kickIframe) {
        kickIframe.addEventListener('error', () => {
            console.log('[Kick Monitor] Erro detectado no iframe');
            showKickLoading();
            scheduleKickReload();
        });

        // Listener para detectar quando o iframe carrega
        kickIframe.addEventListener('load', () => {
            console.log('[Kick Monitor] Iframe carregou');
            // Aguardar um pouco para garantir que o conteúdo está ok
            setTimeout(() => {
                if (isKickLoading) {
                    hideKickLoading();
                }
            }, 3000);
        });
    }
}

/**
 * Verifica o status do iframe da Kick
 */
function checkKickStatus() {
    const kickIframe = document.getElementById('kick-iframe');
    const loadingOverlay = document.getElementById('kick-loading');

    if (!kickIframe) {
        console.log('[Kick Monitor] Iframe não encontrado');
        return;
    }

    // Verificar se o iframe existe e tem src válido
    try {
        const iframeSrc = kickIframe.src;
        if (!iframeSrc || iframeSrc === 'about:blank') {
            console.log('[Kick Monitor] Iframe sem source válido');
            showKickLoading();
            scheduleKickReload();
        }
    } catch (e) {
        console.log('[Kick Monitor] Erro ao verificar iframe:', e);
    }
}

/**
 * Mostra o overlay de carregamento
 */
function showKickLoading() {
    const loadingOverlay = document.getElementById('kick-loading');
    if (loadingOverlay && !isKickLoading) {
        console.log('[Kick Monitor] Mostrando overlay de carregamento');
        isKickLoading = true;
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.animation = 'fadeIn 0.3s ease';
    }
}

/**
 * Esconde o overlay de carregamento
 */
function hideKickLoading() {
    const loadingOverlay = document.getElementById('kick-loading');
    if (loadingOverlay && isKickLoading) {
        console.log('[Kick Monitor] Escondendo overlay de carregamento');
        loadingOverlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
            isKickLoading = false;
        }, 300);
    }
}

/**
 * Agenda um reload do iframe da Kick
 */
function scheduleKickReload() {
    // Limpar timeout anterior se existir
    if (kickReloadTimeout) {
        clearTimeout(kickReloadTimeout);
    }

    // Agendar reload em 5 segundos
    kickReloadTimeout = setTimeout(() => {
        reloadKickIframe();
    }, 5000);
}

/**
 * Recarrega apenas o iframe da Kick (não a página inteira)
 */
function reloadKickIframe() {
    console.log('[Kick Monitor] Recarregando iframe da Kick...');

    const kickContainer = document.getElementById("kick-player");
    if (kickContainer) {
        // Mostrar loading
        showKickLoading();

        // Remover iframe atual
        const oldIframe = document.getElementById('kick-iframe');
        if (oldIframe) {
            oldIframe.remove();
        }

        // Criar novo iframe
        const newIframe = document.createElement('iframe');
        newIframe.id = 'kick-iframe';
        newIframe.src = `https://player.kick.com/${CONFIG.kickChannel}?autoplay=true&muted=false&volume=1&ui-logo=false&ui-info=false`;
        newIframe.frameBorder = '0';
        newIframe.scrolling = 'no';
        newIframe.allowFullscreen = true;
        newIframe.allow = 'autoplay; encrypted-media';
        newIframe.style.cssText = 'width: 100%; height: 100%; border: none;';

        // Listener para quando carregar
        newIframe.addEventListener('load', () => {
            console.log('[Kick Monitor] Novo iframe carregou');
            // Aguardar para garantir que está funcionando
            setTimeout(() => {
                hideKickLoading();
            }, 3000);
        });

        // Listener para erros
        newIframe.addEventListener('error', () => {
            console.log('[Kick Monitor] Erro no novo iframe');
            // Tentar novamente em 10 segundos
            setTimeout(reloadKickIframe, 10000);
        });

        kickContainer.appendChild(newIframe);
    }
}

/**
 * Força o reload manual do iframe (pode ser chamada de fora se necessário)
 */
function forceKickReload() {
    showKickLoading();
    setTimeout(reloadKickIframe, 1000);
}

// Expor funções globalmente para uso externo se necessário
window.showKickLoading = showKickLoading;
window.hideKickLoading = hideKickLoading;
window.reloadKickIframe = reloadKickIframe;
window.forceKickReload = forceKickReload;

// ============================================
// SISTEMA DE DICA FULLSCREEN (Mobile)
// ============================================

function initFullscreenHint() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    if (!isMobile) return;

    const hintOverlay = document.getElementById('fullscreen-hint');
    const hintBtn = document.getElementById('fullscreen-hint-btn');

    if (!hintOverlay || !hintBtn) return;

    // Verificar se já mostrou nesta sessão
    try {
        if (sessionStorage.getItem('fullscreen_hint_shown') === 'true') return;
    } catch (e) {}

    // Mostrar dica após 2s se estiver em portrait
    setTimeout(() => {
        if (window.innerHeight > window.innerWidth) {
            hintOverlay.classList.add('visible');
            // Auto-hide após 6 segundos
            setTimeout(hideHint, 6000);
        }
    }, 2000);

    hintBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideHint();
    });

    function hideHint() {
        hintOverlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            hintOverlay.classList.remove('visible');
            hintOverlay.style.animation = '';
        }, 300);
        try { sessionStorage.setItem('fullscreen_hint_shown', 'true'); } catch (e) {}
    }
}

// ============================================
// SISTEMA DE PAUSE BUTTON (FUNCIONAL)
// ============================================

function initPauseButton() {
    const pauseBtn = document.getElementById('kick-pause-btn');
    const kickContainer = document.querySelector('.kick-player-container');

    if (!pauseBtn || !kickContainer) return;

    let isPaused = false;
    let savedIframeSrc = null; // Guardar o src do iframe para restaurar

    // Criar overlay de pausa
    let pauseOverlay = document.createElement('div');
    pauseOverlay.className = 'kick-paused-overlay';
    pauseOverlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);display:none;align-items:center;justify-content:center;z-index:20;flex-direction:column;gap:20px;cursor:pointer;';
    pauseOverlay.innerHTML = `
        <svg viewBox="0 0 24 24" fill="var(--kick-green)" style="width:80px;height:80px;opacity:0.9;filter:drop-shadow(0 0 20px rgba(83,252,24,0.5));">
            <path d="M8 5v14l11-7z"/>
        </svg>
        <p style="color:#fff;font-size:16px;font-weight:800;text-transform:uppercase;letter-spacing:3px;text-shadow:0 2px 10px rgba(0,0,0,0.5);">CLIQUE PARA RETOMAR</p>
    `;
    kickContainer.appendChild(pauseOverlay);

    // Função para pausar (remove src do iframe)
    function pauseStream() {
        const kickIframe = document.getElementById('kick-iframe');
        if (kickIframe && kickIframe.src) {
            savedIframeSrc = kickIframe.src;
            kickIframe.src = 'about:blank'; // Remove a transmissão
            console.log('[Pause] Transmissão pausada');
        }
        isPaused = true;
        pauseBtn.classList.add('paused');
        pauseOverlay.style.display = 'flex';
    }

    // Função para retomar (restaura src do iframe)
    function resumeStream() {
        const kickIframe = document.getElementById('kick-iframe');
        if (kickIframe && savedIframeSrc) {
            kickIframe.src = savedIframeSrc;
            console.log('[Pause] Transmissão retomada');
        } else if (kickIframe) {
            // Se não tiver src salvo, recarrega o player
            kickIframe.src = `https://player.kick.com/${CONFIG.kickChannel}?autoplay=true&muted=false&volume=1&ui-logo=false&ui-info=false`;
        }
        isPaused = false;
        pauseBtn.classList.remove('paused');
        pauseOverlay.style.display = 'none';
    }

    // Clique no botão de pause
    pauseBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (isPaused) {
            resumeStream();
        } else {
            pauseStream();
        }
    });

    // Clique no overlay também retoma
    pauseOverlay.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (isPaused) {
            resumeStream();
        }
    });

    // Mostrar botão após 3 segundos
    setTimeout(() => pauseBtn.classList.add('visible'), 3000);
}

// Iniciar após overlay de boas-vindas
document.addEventListener('DOMContentLoaded', () => {
    const checkOverlay = setInterval(() => {
        if (!document.querySelector('.unmute-overlay-agressive')) {
            clearInterval(checkOverlay);
            setTimeout(initFullscreenHint, 1000);
            setTimeout(initPauseButton, 500);
        }
    }, 500);
});
