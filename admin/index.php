<?php
/**
 * TV One Portal - Painel de Administração
 * Versão Automação Total (Sem Chaves de API)
 */

require_once __DIR__ . '/../config/functions.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$config = loadConfig();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - TV One Portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .movie-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        .movie-card:hover {
            border-color: var(--kick-green);
        }
        .movie-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        .movie-number {
            font-weight: bold;
            color: var(--kick-green);
            font-size: 1.2em;
        }
        .movie-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .movie-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-save {
            background: var(--kick-green);
            color: #000;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
        }
        .btn-duplicate {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-delete {
            background: #ff4444;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-ai {
            background: #8e44ad;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 5px;
        }
        .btn-translate {
            background: #e67e22;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 5px;
        }
        .btn-translate:hover {
            background: #d35400;
        }
        .btn-clear-all {
            background: #c0392b;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }
        .btn-clear-all:hover {
            background: #a93226;
        }
        .title-input-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .title-input-group input {
            flex: 1;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: var(--kick-green);
            color: #000;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: none;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Estilos para a busca de filmes */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #222;
            border: 1px solid #444;
            border-radius: 0 0 8px 8px;
            z-index: 100;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .search-item {
            padding: 10px;
            cursor: pointer;
            display: flex;
            gap: 10px;
            align-items: center;
            border-bottom: 1px solid #333;
        }
        .search-item:hover {
            background: #333;
        }
        .search-item img {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .search-item .info {
            display: flex;
            flex-direction: column;
        }
        .search-item .info .title {
            font-weight: bold;
            color: #fff;
        }
        .search-item .info .year {
            font-size: 12px;
            color: #aaa;
        }
        .search-item .info .original-title {
            font-size: 11px;
            color: #888;
            font-style: italic;
        }
        .search-item img {
            transition: opacity 0.2s;
        }
        .search-results::-webkit-scrollbar {
            width: 6px;
        }
        .search-results::-webkit-scrollbar-thumb {
            background: var(--kick-green);
            border-radius: 3px;
        }
        .form-group {
            position: relative;
        }

        /* Estilos para o seletor de capas */
        .cover-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 8px;
        }
        .cover-preview-container {
            display: flex;
            gap: 10px;
            overflow: hidden;
            flex: 1;
            justify-content: center;
        }
        .cover-option {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .cover-option.selected {
            border-color: var(--kick-green);
            transform: scale(1.05);
        }
        .cover-nav {
            background: #444;
            color: #fff;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .cover-nav:hover {
            background: var(--kick-green);
            color: #000;
        }
        .cover-nav:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div id="toast" class="toast"></div>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <h2>TV One</h2>
                <p>Painel Admin</p>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php" class="active">Programação</a></li>
                    <li><a href="stats.php">Estatísticas</a></li>
                    <li><a href="security.php">Segurança</a></li>
                    <li><a href="logs.php">Logs</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>⚙️ Gerenciar Conteúdo</h1>
                <a href="../" target="_blank" class="btn-outline" style="text-decoration: none; padding: 5px 15px; border-radius: 5px; border: 1px solid #555; color: #ccc; font-size: 12px;">👁️ Ver Site</a>
            </header>

            <div class="admin-content">
                <!-- Votação -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>🗳️ Votação: Escolha 1 Filme</span>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                            <input type="checkbox" id="vote_active" <?php echo ($config['voting']['active'] ?? false) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                            Janela Ativa
                        </label>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Título da Janela de Votação</label>
                            <input type="text" id="voting_title" value="<?php echo htmlspecialchars($config['voting']['voting_title'] ?? 'ESCOLHA O FILME QUE VOCÊ QUER VER AMANHÃ'); ?>" placeholder="Ex: ESCOLHA O FILME QUE VOCÊ QUER VER AMANHÃ">
                        </div>
                        <div class="movie-grid">
                            <div class="stat-card" style="text-align: left; background: rgba(255,255,255,0.02);">
                                <label>Filme 1</label>
                                <input type="text" id="vote_title1" value="<?php echo htmlspecialchars($config['voting']['movie1']['title']); ?>" placeholder="Título" onkeyup="searchMovieUI(this, 'vote1')">
                                <div id="results-vote1" class="search-results"></div>
                                <input type="text" id="vote_image1" value="<?php echo htmlspecialchars($config['voting']['movie1']['image']); ?>" placeholder="URL da Capa" style="margin-top: 10px;">
                                <div id="cover-selector-vote1" class="cover-selector" style="display:none;">
                                    <button class="cover-nav prev" onclick="navigateCovers('vote1', -1)">❮</button>
                                    <div class="cover-preview-container" id="covers-vote1"></div>
                                    <button class="cover-nav next" onclick="navigateCovers('vote1', 1)">❯</button>
                                </div>
                                <div style="margin-top: 10px; font-size: 12px; color: var(--kick-green);">Votos: <?php echo $config['voting']['movie1']['votes']; ?></div>
                            </div>
                            <div class="stat-card" style="text-align: left; background: rgba(255,255,255,0.02);">
                                <label>Filme 2</label>
                                <input type="text" id="vote_title2" value="<?php echo htmlspecialchars($config['voting']['movie2']['title']); ?>" placeholder="Título" onkeyup="searchMovieUI(this, 'vote2')">
                                <div id="results-vote2" class="search-results"></div>
                                <input type="text" id="vote_image2" value="<?php echo htmlspecialchars($config['voting']['movie2']['image']); ?>" placeholder="URL da Capa" style="margin-top: 10px;">
                                <div id="cover-selector-vote2" class="cover-selector" style="display:none;">
                                    <button class="cover-nav prev" onclick="navigateCovers('vote2', -1)">❮</button>
                                    <div class="cover-preview-container" id="covers-vote2"></div>
                                    <button class="cover-nav next" onclick="navigateCovers('vote2', 1)">❯</button>
                                </div>
                                <div style="margin-top: 10px; font-size: 12px; color: var(--kick-green);">Votos: <?php echo $config['voting']['movie2']['votes']; ?></div>
                            </div>
                        </div>
                        <div class="movie-actions">
                            <button onclick="saveVoting()" class="btn-save">Salvar Votação</button>
                            <button onclick="resetVotes()" class="btn-delete">Zerar Tudo</button>
                        </div>
                    </div>
                </div>

                <!-- Programação -->
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <h2>🎬 Grade de Programação</h2>
                    <div>
                        <button onclick="addMovie()" class="btn-save" style="font-size: 12px;">+ Novo Filme</button>
                        <button onclick="clearAllMovies()" class="btn-clear-all" style="font-size: 12px;">🗑️ Apagar Todos</button>
                    </div>
                </div>

                <div id="schedule-list">
                    <?php foreach ($config['schedule'] as $index => $movie): ?>
                    <div class="movie-card" id="movie-<?php echo $index; ?>">
                        <div class="movie-header">
                            <span class="movie-number">Filme <?php echo $index + 1; ?></span>
                        </div>
                        <div class="movie-grid">
                            <div class="form-group">
                                <label>Título do Filme</label>
                                <div class="title-input-group">
                                    <input type="text" class="m-title" value="<?php echo htmlspecialchars($movie['title']); ?>" onkeyup="searchMovieUI(this, <?php echo $index; ?>)">
                                    <button onclick="translateTitle(<?php echo $index; ?>)" class="btn-translate" title="Traduzir para Português">🌐 Traduzir</button>
                                </div>
                                <div id="results-<?php echo $index; ?>" class="search-results"></div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label>Ano</label>
                                    <input type="text" class="m-year" value="<?php echo htmlspecialchars($movie['year']); ?>">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label>Horário</label>
                                    <input type="time" class="m-time" value="<?php echo htmlspecialchars($movie['time']); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label>URL da Capa</label>
                            <input type="text" class="m-image" value="<?php echo htmlspecialchars($movie['image']); ?>">
                            <div id="cover-selector-<?php echo $index; ?>" class="cover-selector" style="display:none;">
                                <button class="cover-nav prev" onclick="navigateCovers(<?php echo $index; ?>, -1)">❮</button>
                                <div class="cover-preview-container" id="covers-<?php echo $index; ?>"></div>
                                <button class="cover-nav next" onclick="navigateCovers(<?php echo $index; ?>, 1)">❯</button>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label>Sinopse</label>
                                <button onclick="generateSynopsis(<?php echo $index; ?>)" class="btn-ai">✨ Gerar Sinopse</button>
                            </div>
                            <textarea class="m-synopsis" style="height: 60px;"><?php echo htmlspecialchars($movie['synopsis']); ?></textarea>
                        </div>
                        <div class="movie-actions">
                            <button onclick="deleteMovie(<?php echo $index; ?>)" class="btn-delete">Excluir</button>
                            <button onclick="duplicateMovie(<?php echo $index; ?>)" class="btn-duplicate">Duplicar</button>
                            <button onclick="saveMovie(<?php echo $index; ?>)" class="btn-save">Salvar Filme</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        let searchTimeout;
        let movieCovers = {};
        let searchCache = {}; // Cache local para buscas
        let abortController = null; // Para cancelar requisições anteriores

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 3000);
        }

        function searchMovieUI(input, index) {
            clearTimeout(searchTimeout);
            const query = input.value.trim();
            const resultsDiv = document.getElementById('results-' + index);

            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            // Verifica cache primeiro (resposta instantânea)
            if (searchCache[query]) {
                renderSearchResults(searchCache[query], resultsDiv, index);
                return;
            }

            // Mostra loading
            resultsDiv.innerHTML = '<div class="search-item" style="justify-content: center; color: #53fc18;">🔍 Buscando filmes...</div>';
            resultsDiv.style.display = 'block';

            // Cancela requisição anterior se houver
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            searchTimeout = setTimeout(() => {
                const data = new FormData();
                data.append('action', 'search_movie');
                data.append('query', query);

                fetch('api.php', {
                    method: 'POST',
                    body: data,
                    signal: abortController.signal
                })
                .then(r => r.json())
                .then(res => {
                    if (res.results && res.results.length > 0) {
                        // Salva no cache
                        searchCache[query] = res.results;
                        renderSearchResults(res.results, resultsDiv, index);
                    } else {
                        resultsDiv.innerHTML = '<div class="search-item" style="justify-content: center; color: #ff6b6b;">Nenhum filme encontrado</div>';
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Erro na busca:', err);
                        resultsDiv.innerHTML = '<div class="search-item" style="color: #ff6b6b;">Erro na busca</div>';
                    }
                });
            }, 250); // Debounce de 250ms (rápido mas evita spam)
        }

        function renderSearchResults(results, resultsDiv, index) {
            resultsDiv.innerHTML = '';
            results.forEach(movie => {
                const item = document.createElement('div');
                item.className = 'search-item';
                const poster = movie.poster ? movie.poster : 'https://via.placeholder.com/40x60/333/53fc18?text=?';
                const year = movie.year ? movie.year : '';
                const titleOriginal = movie.title_original && movie.title_original !== movie.title
                    ? `<span class="original-title">${movie.title_original}</span>` : '';

                item.innerHTML = `
                    <img src="${poster}" onerror="this.src='https://via.placeholder.com/40x60/333/53fc18?text=?'">
                    <div class="info">
                        <span class="title">${movie.title}</span>
                        ${titleOriginal}
                        <span class="year">${year}</span>
                    </div>
                `;
                item.onclick = () => selectMovie(movie, index);
                resultsDiv.appendChild(item);
            });
            resultsDiv.style.display = 'block';
        }

        function selectMovie(movie, index) {
            const poster = movie.poster ? movie.poster : '';
            const year = movie.year ? movie.year : '';
            
            if (index === 'vote1' || index === 'vote2') {
                document.getElementById('vote_title' + index.replace('vote', '')).value = movie.title;
                document.getElementById('vote_image' + index.replace('vote', '')).value = poster;
                document.getElementById('results-' + index).style.display = 'none';
                loadMovieCovers(movie.title, index);
            } else {
                const card = document.getElementById('movie-' + index);
                card.querySelector('.m-title').value = movie.title;
                card.querySelector('.m-year').value = year;
                card.querySelector('.m-image').value = poster;
                document.getElementById('results-' + index).style.display = 'none';
                loadMovieCovers(movie.title, index);
            }
        }

        function loadMovieCovers(title, index) {
            const data = new FormData();
            data.append('action', 'get_movie_images');
            data.append('title', title);

            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.posters && res.posters.length > 0) {
                        movieCovers[index] = {
                            list: res.posters,
                            current: 0
                        };
                        renderCovers(index);
                    }
                });
        }

        function renderCovers(index) {
            const container = document.getElementById('covers-' + index);
            const selector = document.getElementById('cover-selector-' + index);
            const covers = movieCovers[index];
            
            if (!covers || covers.list.length === 0) return;
            
            selector.style.display = 'flex';
            container.innerHTML = '';
            
            const start = covers.current;
            const toShow = covers.list.slice(start, start + 2);
            
            toShow.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'cover-option';
                const currentUrl = (index === 'vote1' || index === 'vote2') 
                    ? document.getElementById('vote_image' + index.replace('vote', '')).value 
                    : document.getElementById('movie-' + index).querySelector('.m-image').value;
                
                if (url === currentUrl) img.classList.add('selected');
                
                img.onclick = () => {
                    if (index === 'vote1' || index === 'vote2') {
                        document.getElementById('vote_image' + index.replace('vote', '')).value = url;
                    } else {
                        document.getElementById('movie-' + index).querySelector('.m-image').value = url;
                    }
                    renderCovers(index);
                };
                container.appendChild(img);
            });

            selector.querySelector('.prev').disabled = covers.current === 0;
            selector.querySelector('.next').disabled = covers.current >= covers.list.length - 2;
        }

        function navigateCovers(index, direction) {
            if (!movieCovers[index]) return;
            const covers = movieCovers[index];
            covers.current += direction;
            if (covers.current < 0) covers.current = 0;
            if (covers.current > covers.list.length - 2) covers.current = covers.list.length - 2;
            renderCovers(index);
        }

        function generateSynopsis(index) {
            const card = document.getElementById('movie-' + index);
            const title = card.querySelector('.m-title').value;
            const btn = card.querySelector('.btn-ai');
            
            if (!title || title === 'Novo Filme') {
                alert('Por favor, digite o nome do filme primeiro.');
                return;
            }

            btn.textContent = '⏳ Buscando...';
            btn.disabled = true;

            const data = new FormData();
            data.append('action', 'generate_synopsis');
            data.append('title', title);

            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        card.querySelector('.m-synopsis').value = res.synopsis;
                        showToast('Sinopse atualizada!');
                    } else {
                        alert('Erro: ' + res.message);
                    }
                })
                .finally(() => {
                    btn.textContent = '✨ Gerar Sinopse';
                    btn.disabled = false;
                });
        }

        function saveMovie(index) {
            const card = document.getElementById('movie-' + index);
            const data = new FormData();
            data.append('action', 'save_movie');
            data.append('index', index);
            data.append('title', card.querySelector('.m-title').value);
            data.append('year', card.querySelector('.m-year').value);
            data.append('time', card.querySelector('.m-time').value);
            data.append('image', card.querySelector('.m-image').value);
            data.append('synopsis', card.querySelector('.m-synopsis').value);

            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => { if(res.success) showToast(res.message); });
        }

        function addMovie() {
            const data = new FormData();
            data.append('action', 'add_movie');
            fetch('api.php', { method: 'POST', body: data })
                .then(() => location.reload());
        }

        function deleteMovie(index) {
            if(!confirm('Remover este filme?')) return;
            const data = new FormData();
            data.append('action', 'delete_movie');
            data.append('index', index);
            fetch('api.php', { method: 'POST', body: data })
                .then(() => location.reload());
        }

        function duplicateMovie(index) {
            const data = new FormData();
            data.append('action', 'duplicate_movie');
            data.append('index', index);
            
            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        showToast(res.message);
                        setTimeout(() => location.reload(), 500);
                    }
                });
        }

        function saveVoting() {
            const data = new FormData();
            data.append('action', 'save_voting');
            data.append('vote_title1', document.getElementById('vote_title1').value);
            data.append('vote_image1', document.getElementById('vote_image1').value);
            data.append('vote_title2', document.getElementById('vote_title2').value);
            data.append('vote_image2', document.getElementById('vote_image2').value);
            data.append('voting_title', document.getElementById('voting_title').value);
            data.append('active', document.getElementById('vote_active').checked);

            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => { if(res.success) showToast(res.message); });
        }

        function resetVotes() {
            if(!confirm('Zerar todos os votos agora?')) return;
            const data = new FormData();
            data.append('action', 'reset_votes');
            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        showToast(res.message);
                        setTimeout(() => location.reload(), 1000);
                    }
                });
        }

        function clearAllMovies() {
            if(!confirm('⚠️ ATENÇÃO!\n\nIsso irá apagar TODOS os filmes da grade de programação.\n\nTem certeza que deseja continuar?')) return;
            const data = new FormData();
            data.append('action', 'clear_all_movies');
            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        showToast(res.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                });
        }

        function translateTitle(index) {
            const card = document.getElementById('movie-' + index);
            const titleInput = card.querySelector('.m-title');
            const title = titleInput.value;
            const btn = card.querySelector('.btn-translate');

            if (!title || title === 'Novo Filme') {
                alert('Por favor, digite o nome do filme primeiro.');
                return;
            }

            btn.textContent = '⏳...';
            btn.disabled = true;

            const data = new FormData();
            data.append('action', 'translate_title');
            data.append('title', title);

            fetch('api.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.translated_title) {
                        titleInput.value = res.translated_title;
                        showToast('Título traduzido!');
                    } else {
                        alert('Não foi possível traduzir. ' + (res.message || ''));
                    }
                })
                .catch(err => {
                    alert('Erro ao traduzir: ' + err);
                })
                .finally(() => {
                    btn.textContent = '🌐 Traduzir';
                    btn.disabled = false;
                });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('m-title') && !e.target.closest('.search-results')) {
                document.querySelectorAll('.search-results').forEach(el => el.style.display = 'none');
            }
        });
    </script>
</body>
</html>
