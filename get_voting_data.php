<?php
/**
 * TV One Portal - API de Dados de Votação
 * Retorna informações sobre votação ativa e status do usuário
 */

require_once __DIR__ . '/config/functions.php';

header('Content-Type: application/json');

$config = loadConfig();
$voting = $config['voting'] ?? [
    'movie1' => ['title' => '', 'image' => '', 'votes' => 0],
    'movie2' => ['title' => '', 'image' => '', 'votes' => 0],
    'voters_ips' => [],
    'current_vote_id' => 1
];

// Verifica se há votação ativa (configurada pelo admin e ambos os filmes presentes)
$is_active = isset($voting['active']) && $voting['active'] === true;
$has_movies = !empty($voting['movie1']['title']) && !empty($voting['movie2']['title']);
$has_voting = $is_active && $has_movies;

// Verifica se o usuário já votou e em quem
$user_ip = $_SERVER['REMOTE_ADDR'];
$vote_id = $voting['current_vote_id'] ?? 1;
$vote_cookie_name = "voted_id_" . $vote_id;
$voted_for = $_COOKIE[$vote_cookie_name] ?? null;

// Se não tiver cookie, verifica no IP (mas o IP não diz em quem votou no modelo atual)
$has_voted_by_ip = in_array($user_ip, $voting['voters_ips'] ?? []);
$has_voted = ($voted_for !== null) || $has_voted_by_ip;

// Se for administrador, sempre permite ver o popup para testes (como não votado)
if (isAdminAuthenticated()) {
    // $has_voted = false; // Removido para permitir teste de estado "já votou" se quiser
}

$response = [
    'has_voting' => $has_voting,
    'has_voted' => $has_voted,
    'voted_for' => $voted_for, // 'movie1' ou 'movie2'
    'voting' => $voting
];

echo json_encode($response);
?>
