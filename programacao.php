<?php
/**
 * TV One Portal - Página de Programação
 * Exibe todas as capas da programação em formato grid com horário
 * Inclui sistema de votação real com trava de IP e Cookies
 */

require_once __DIR__ . '/config/functions.php';

$config = loadConfig();
$schedule = $config['schedule'] ?? [];

// Rastrear acesso
trackAccess();
$voting = $config['voting'] ?? [
    'movie1' => ['title' => '', 'image' => '', 'votes' => 0],
    'movie2' => ['title' => '', 'image' => '', 'votes' => 0],
    'voters_ips' => [],
    'current_vote_id' => 1
];

$user_ip = $_SERVER['REMOTE_ADDR'];
$vote_cookie_name = "voted_id_" . ($voting['current_vote_id'] ?? 1);
$has_voted = isset($_COOKIE[$vote_cookie_name]) || in_array($user_ip, $voting['voters_ips'] ?? []);

// Lógica de Processamento de Voto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option'])) {
    $option = $_POST['vote_option'];
    if (($option === 'movie1' || $option === 'movie2') && !$has_voted) {
        $config['voting'][$option]['votes']++;
        if (!isset($config['voting']['voters_ips'])) $config['voting']['voters_ips'] = [];
        $config['voting']['voters_ips'][] = $user_ip;
        saveConfig($config);
        
        // Define cookie por 30 dias (armazena a opção escolhida)
        setcookie($vote_cookie_name, $option, time() + (86400 * 30), "/");
        $has_voted = true;
        $response = ['success' => true, 'votes' => $config['voting']];
    } else {
        $response = ['success' => false, 'message' => 'Você já votou nesta rodada!'];
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Ordenar a programação por horário
usort($schedule, function($a, $b) {
    $timeA = $a['time'] ?? '99:99';
    $timeB = $b['time'] ?? '99:99';
    return strcmp($timeA, $timeB);
});

// Cálculo de porcentagens
$total_votes = $voting['movie1']['votes'] + $voting['movie2']['votes'];
$perc1 = $total_votes > 0 ? round(($voting['movie1']['votes'] / $total_votes) * 100) : 50;
$perc2 = $total_votes > 0 ? round(($voting['movie2']['votes'] / $total_votes) * 100) : 50;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qual Passa? - <?php echo htmlspecialchars($config['site_title']); ?></title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        :root {
            --kick-green: <?php echo htmlspecialchars($config['theme_primary_color']); ?>;
            --bg-primary: <?php echo htmlspecialchars($config['theme_background']); ?>;
            --bg-secondary: <?php echo htmlspecialchars($config['theme_sidebar']); ?>;
            --text-primary: #ffffff;
            --text-secondary: #adadb8;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: 30px;
        }
        
        .header {
            position: sticky; top: 0; z-index: 100;
            background: linear-gradient(to right, var(--bg-secondary), #1a1a1a);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        .back-button {
            background: var(--kick-green); color: #000; border: none; border-radius: 6px;
            padding: 8px 15px; font-size: 12px; font-weight: 800; text-transform: uppercase;
            cursor: pointer; text-decoration: none;
        }
        
        .container {
            max-width: 1000px; /* Largura fixa para alinhar tudo */
            margin: 0 auto;
            padding: 15px;
        }

        .voting-section {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .voting-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .vote-card {
            background: var(--bg-secondary);
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .vote-card.voted { border-color: var(--kick-green); }

        .vote-poster-wrapper { width: 100%; aspect-ratio: 2/3; overflow: hidden; }
        .vote-poster { width: 100%; height: 100%; object-fit: cover; }

        .vote-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%;
            padding: 15px 10px; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        }

        .progress-container {
            background: rgba(255, 255, 255, 0.1); height: 8px; border-radius: 4px;
            overflow: hidden; margin-bottom: 5px;
        }

        .progress-bar { height: 100%; background: var(--kick-green); transition: width 1s ease; }
        .vote-percentage { font-size: 18px; font-weight: 900; color: var(--kick-green); }

        .section-divider {
            display: flex; align-items: center; gap: 15px; margin-bottom: 20px;
        }

        .section-divider h3 { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); }
        .section-divider .line { height: 1px; background: var(--border-color); flex: 1; }
        
        /* AJUSTE DA GRADE: Alinhada com a largura da votação */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); /* Capas maiores para alinhar */
            gap: 15px;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr 1fr; /* 2 colunas no mobile para alinhar com a votação */
            }
        }

        .movie-card {
            background: var(--bg-secondary);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            display: flex; flex-direction: column;
        }
        
        .movie-poster-small { width: 100%; aspect-ratio: 2/3; object-fit: cover; }
        
        .movie-info { padding: 10px; flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .movie-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #fff; }
        .movie-time { font-size: 10px; color: var(--kick-green); font-weight: 800; }

        #toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            background: var(--kick-green); color: #000; padding: 12px 25px;
            border-radius: 50px; font-weight: 800; display: none; z-index: 2000;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="back-button">← VOLTAR</a>
        <div style="font-size: 14px; font-weight: 800; color: var(--kick-green);">Escolha 1 Filme</div>
    </header>
    
    <div class="container">
        <?php if (!empty($voting['movie1']['title'])): ?>
        <section class="voting-section">
            <h2 style="font-size: 16px; margin-bottom: 20px;">🗳️ QUAL FILME PASSA AMANHÃ?</h2>
            <div class="voting-grid">
                <div class="vote-card <?php echo $has_voted ? 'voted' : ''; ?>" onclick="castVote('movie1')">
                    <div class="vote-poster-wrapper">
                        <img src="<?php echo htmlspecialchars($voting['movie1']['image']); ?>" class="vote-poster">
                        <div class="vote-overlay">
                            <div style="font-size: 12px; font-weight: 800; margin-bottom: 5px;"><?php echo htmlspecialchars($voting['movie1']['title']); ?></div>
                            <div class="progress-container">
                                <div id="bar-movie1" class="progress-bar" style="width: <?php echo $perc1; ?>%"></div>
                            </div>
                            <div id="perc-movie1" class="vote-percentage"><?php echo $perc1; ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="vote-card <?php echo $has_voted ? 'voted' : ''; ?>" onclick="castVote('movie2')">
                    <div class="vote-poster-wrapper">
                        <img src="<?php echo htmlspecialchars($voting['movie2']['image']); ?>" class="vote-poster">
                        <div class="vote-overlay">
                            <div style="font-size: 12px; font-weight: 800; margin-bottom: 5px;"><?php echo htmlspecialchars($voting['movie2']['title']); ?></div>
                            <div class="progress-container">
                                <div id="bar-movie2" class="progress-bar" style="width: <?php echo $perc2; ?>%"></div>
                            </div>
                            <div id="perc-movie2" class="vote-percentage"><?php echo $perc2; ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="section-divider">
            <h3>GRADE DE HOJE</h3>
            <div class="line"></div>
        </div>

        <?php if (!empty($schedule)): ?>
            <div class="schedule-grid">
                <?php foreach ($schedule as $item): ?>
                <div class="movie-card">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="movie-poster-small">
                    <div class="movie-info">
                        <div class="movie-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="movie-time"><?php echo htmlspecialchars($item['time']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="toast"></div>
    
    <script>
        function castVote(option) {
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
                    updateBars(data.votes);
                    showToast("Voto registrado!");
                    document.querySelectorAll('.vote-card').forEach(c => c.classList.add('voted'));
                } else {
                    showToast(data.message);
                }
            });
        }

        function updateBars(votes) {
            const v1 = parseInt(votes.movie1.votes);
            const v2 = parseInt(votes.movie2.votes);
            const total = v1 + v2;
            const p1 = total > 0 ? Math.round((v1 / total) * 100) : 50;
            const p2 = total > 0 ? Math.round((v2 / total) * 100) : 50;
            document.getElementById('bar-movie1').style.width = p1 + '%';
            document.getElementById('perc-movie1').textContent = p1 + '%';
            document.getElementById('bar-movie2').style.width = p2 + '%';
            document.getElementById('perc-movie2').textContent = p2 + '%';
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }
    </script>
</body>
</html>
