<?php
/**
 * TV One Portal - Página de Logs
 */

require_once __DIR__ . '/../config/functions.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$config = loadConfig();
$logFile = __DIR__ . '/../config/activity.log';
$logs = [];

// Lê o arquivo de log
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = array_reverse(explode("\n", trim($logContent)));
    
    foreach ($logLines as $line) {
        if (!empty($line)) {
            $logs[] = $line;
        }
    }
}

// Limita a 100 linhas mais recentes
$logs = array_slice($logs, 0, 100);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Painel de Administração</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --kick-green: <?php echo htmlspecialchars($config['theme_primary_color']); ?>;
        }

        .log-entry {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: var(--text-secondary);
            overflow-x: auto;
        }

        .log-entry:hover {
            background-color: var(--bg-tertiary);
        }

        .log-timestamp {
            color: var(--kick-green);
            font-weight: 600;
        }

        .log-action {
            color: var(--text-primary);
            margin: 0 10px;
        }

        .log-details {
            color: var(--text-tertiary);
        }

        .logs-container {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-height: 600px;
            overflow-y: auto;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: var(--text-tertiary);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <h2>TV One</h2>
                <p>Painel Admin</p>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php">Configurações</a></li>
                    <li><a href="stats.php">Estatísticas</a></li>
                    <li><a href="security.php">Segurança</a></li>
                    <li><a href="logs.php" class="active">Logs</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>📋 Logs de Atividade</h1>
                <div>
                    <span style="font-size: 12px; color: var(--text-secondary);">
                        Total: <?php echo count($logs); ?> registros
                    </span>
                </div>
            </header>

            <div class="admin-content">
                <!-- Informações -->
                <div class="card">
                    <div class="card-header">Atividades Recentes</div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>Nenhum log registrado ainda</p>
                            </div>
                        <?php else: ?>
                            <div class="logs-container">
                                <?php foreach ($logs as $log): ?>
                                    <div class="log-entry">
                                        <?php
                                        // Tenta parsear o log
                                        preg_match('/\[(.*?)\].*?IP: (.*?)\s*\|\s*Action: (.*?)\s*\|\s*Details: (.*?)$/', $log, $matches);
                                        
                                        if (!empty($matches)) {
                                            $timestamp = $matches[1];
                                            $ip = $matches[2];
                                            $action = $matches[3];
                                            $details = $matches[4];
                                        } else {
                                            $timestamp = 'N/A';
                                            $ip = 'N/A';
                                            $action = 'N/A';
                                            $details = $log;
                                        }
                                        ?>
                                        <span class="log-timestamp"><?php echo htmlspecialchars($timestamp); ?></span>
                                        <span class="log-action"><?php echo htmlspecialchars($action); ?></span>
                                        <span class="log-details"><?php echo htmlspecialchars($details); ?></span>
                                        <span style="color: var(--text-tertiary); font-size: 11px;"> (<?php echo htmlspecialchars($ip); ?>)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informações do Log -->
                <div class="card">
                    <div class="card-header">Informações</div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Arquivo de Log</strong>
                                <div style="font-size: 12px; word-break: break-all;">
                                    <?php echo file_exists($logFile) ? '✓ Existe' : '✗ Não encontrado'; ?>
                                </div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Tamanho</strong>
                                <div style="font-size: 12px;">
                                    <?php 
                                    if (file_exists($logFile)) {
                                        $size = filesize($logFile);
                                        echo $size > 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B';
                                    } else {
                                        echo '0 B';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Última Modificação</strong>
                                <div style="font-size: 12px;">
                                    <?php 
                                    if (file_exists($logFile)) {
                                        echo date('d/m/Y H:i:s', filemtime($logFile));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group">
                            <a href="index.php" class="btn btn-outline">← Voltar</a>
                            <a href="logout.php" class="btn btn-secondary">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
