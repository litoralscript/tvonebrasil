<?php
/**
 * TV One Portal - Rastreador de Acessos
 * Registra visitas e estatísticas em um arquivo JSON
 */

function trackAccess() {
    $statsFile = __DIR__ . '/stats.json';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $page = basename($_SERVER['PHP_SELF']);
    $date = date('Y-m-d');
    $timestamp = time();

    // Carrega estatísticas existentes ou cria novo array
    if (file_exists($statsFile)) {
        $stats = json_decode(file_get_contents($statsFile), true);
    } else {
        $stats = [
            'total_visits' => 0,
            'unique_visitors' => [],
            'daily_stats' => [],
            'page_views' => [],
            'recent_access' => []
        ];
    }

    // Incrementa visitas totais
    $stats['total_visits']++;

    // Verifica visitante único por IP (limitado aos últimos 1000 IPs para não sobrecarregar o arquivo)
    if (!isset($stats['unique_visitors'])) $stats['unique_visitors'] = [];
    if (!in_array($ip, $stats['unique_visitors'])) {
        $stats['unique_visitors'][] = $ip;
        if (count($stats['unique_visitors']) > 1000) {
            array_shift($stats['unique_visitors']);
        }
    }

    // Estatísticas diárias
    if (!isset($stats['daily_stats'][$date])) {
        $stats['daily_stats'][$date] = ['visits' => 0, 'uniques' => []];
    }
    $stats['daily_stats'][$date]['visits']++;
    if (!in_array($ip, $stats['daily_stats'][$date]['uniques'])) {
        $stats['daily_stats'][$date]['uniques'][] = $ip;
    }

    // Visualizações por página
    if (!isset($stats['page_views'][$page])) {
        $stats['page_views'][$page] = 0;
    }
    $stats['page_views'][$page]++;

    // Registra acesso recente (últimos 50)
    $accessEntry = [
        'ip' => $ip,
        'page' => $page,
        'time' => date('H:i:s'),
        'timestamp' => $timestamp
    ];
    array_unshift($stats['recent_access'], $accessEntry);
    $stats['recent_access'] = array_slice($stats['recent_access'], 0, 50);

    // Salva de volta no arquivo
    @file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
}

/**
 * Retorna as estatísticas formatadas
 */
function getStats() {
    $statsFile = __DIR__ . '/stats.json';
    if (!file_exists($statsFile)) {
        return [
            'total_visits' => 0,
            'unique_count' => 0,
            'daily_stats' => [],
            'page_views' => [],
            'recent_access' => []
        ];
    }
    
    $stats = json_decode(file_get_contents($statsFile), true);
    $stats['unique_count'] = count($stats['unique_visitors'] ?? []);
    return $stats;
}
?>
