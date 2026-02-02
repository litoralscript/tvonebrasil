<?php
/**
 * TV One Portal - Estatísticas de Acesso
 */

require_once __DIR__ . '/../config/functions.php';

// Se não está autenticado, redireciona para login
if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$stats = getStats();
$config = loadConfig();

// Preparar dados para o gráfico (últimos 7 dias)
$dailyLabels = [];
$dailyVisits = [];
$dailyUniques = [];

$dates = array_keys($stats['daily_stats'] ?? []);
sort($dates);
$last7Days = array_slice($dates, -7);

foreach ($last7Days as $date) {
    $dailyLabels[] = date('d/m', strtotime($date));
    $dailyVisits[] = $stats['daily_stats'][$date]['visits'] ?? 0;
    $dailyUniques[] = count($stats['daily_stats'][$date]['uniques'] ?? []);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - TV One Portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--kick-green);
            margin: 10px 0;
        }
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .chart-container {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .stats-table th, .stats-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .stats-table th {
            background: rgba(255,255,255,0.05);
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        .ip-badge {
            background: var(--bg-tertiary);
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <h2>TV One</h2>
                <p>Painel Admin</p>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php">Configurações</a></li>
                    <li><a href="stats.php" class="active">Estatísticas</a></li>
                    <li><a href="logs.php">Logs</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Estatísticas de Acesso</h1>
                <div class="admin-user">Administrador</div>
            </header>

            <div class="admin-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total de Visitas</div>
                        <div class="stat-value"><?php echo $stats['total_visits'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Visitantes Únicos</div>
                        <div class="stat-value"><?php echo $stats['unique_count'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Páginas Vistas</div>
                        <div class="stat-value"><?php echo array_sum($stats['page_views'] ?? []); ?></div>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 style="margin-bottom: 20px;">Acessos nos últimos 7 dias</h3>
                    <canvas id="visitsChart" height="100"></canvas>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="table-container">
                        <h3 style="margin-bottom: 15px;">Acessos Recentes</h3>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>Página</th>
                                    <th>Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($stats['recent_access'] ?? [], 0, 10) as $access): ?>
                                <tr>
                                    <td><span class="ip-badge"><?php echo $access['ip']; ?></span></td>
                                    <td><?php echo $access['page']; ?></td>
                                    <td><?php echo $access['time']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-container">
                        <h3 style="margin-bottom: 15px;">IPs que já Votaram</h3>
                        <div style="max-height: 200px; overflow-y: auto; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>IP do Eleitor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $voters = $config['voting']['voters_ips'] ?? [];
                                    if (empty($voters)): 
                                    ?>
                                    <tr><td>Nenhum voto registrado</td></tr>
                                    <?php else: 
                                    foreach (array_reverse($voters) as $voter_ip): 
                                    ?>
                                    <tr>
                                        <td><span class="ip-badge"><?php echo htmlspecialchars($voter_ip); ?></span></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="table-container">
                        <h3 style="margin-bottom: 15px;">Páginas mais Vistas</h3>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Página</th>
                                    <th>Visualizações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $views = $stats['page_views'] ?? [];
                                arsort($views);
                                foreach (array_slice($views, 0, 10) as $page => $count): 
                                ?>
                                <tr>
                                    <td><?php echo $page; ?></td>
                                    <td><?php echo $count; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('visitsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'Visitas Totais',
                    data: <?php echo json_encode($dailyVisits); ?>,
                    borderColor: '#53fc18',
                    backgroundColor: 'rgba(83, 252, 24, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Visitantes Únicos',
                    data: <?php echo json_encode($dailyUniques); ?>,
                    borderColor: '#9146FF',
                    backgroundColor: 'rgba(145, 70, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#aaa' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#aaa' }
                    }
                }
            }
        });
    </script>
</body>
</html>
