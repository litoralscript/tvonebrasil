<?php
/**
 * TV One Portal - Funções Auxiliares
 * Gerencia configurações, autenticação e operações comuns
 */

// Inicia a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Caminho do arquivo de configurações
define('CONFIG_FILE', __DIR__ . '/settings.json');
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos
require_once __DIR__ . '/tracker.php';

/**
 * Carrega as configurações do arquivo JSON
 * @return array Configurações do portal
 */
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return getDefaultConfig();
    }
    
    $json = file_get_contents(CONFIG_FILE);
    $config = json_decode($json, true);
    
    return $config ?: getDefaultConfig();
}

/**
 * Retorna configurações padrão
 * @return array Configurações padrão
 */
function getDefaultConfig() {
    return [
        'site_title' => 'TV One - Portal de Transmissão',
        'site_description' => 'Assista simultaneamente Twitch e Kick',
        'twitch_channel' => 'tvonebrazil',
        'kick_channel' => 'tvonebrasil',
        'admin_password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
        'donation_link' => 'https://www.twitch.tv/tvonebrazil',
        'sub_button_text' => 'Inscreva-se no Canal',
        'sub_button_link' => 'https://www.twitch.tv/tvonebrazil/subscribe',
        'theme_primary_color' => '#53fc18',
        'theme_secondary_color' => '#9146FF',
        'theme_background' => '#0f0f0f',
        'theme_sidebar' => '#181818',
        'schedule' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * Salva as configurações no arquivo JSON
 * @param array $config Configurações a serem salvas
 * @return bool Sucesso da operação
 */
function saveConfig($config) {
    $config['last_updated'] = date('Y-m-d H:i:s');
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (@file_put_contents(CONFIG_FILE, $json) !== false) {
        return true;
    }
    
    return false;
}

/**
 * Verifica se o usuário está autenticado como admin
 * @return bool Verdadeiro se autenticado
 */
function isAdminAuthenticated() {
    if (!isset($_SESSION['admin_authenticated'])) {
        return false;
    }
    
    // Verifica timeout da sessão
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return $_SESSION['admin_authenticated'] === true;
}

/**
 * Faz login do admin
 * @param string $password Senha fornecida
 * @return bool Sucesso do login
 */
function adminLogin($password) {
    $config = loadConfig();
    
    if (password_verify($password, $config['admin_password_hash'])) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        return true;
    }
    
    return false;
}

/**
 * Faz logout do admin
 */
function adminLogout() {
    session_destroy();
}

/**
 * Sanitiza entrada de usuário
 * @param string $input Entrada a ser sanitizada
 * @return string Entrada sanitizada
 */
function sanitizeInput($input) {
    // Remove tags HTML e espaços extras
    $clean = strip_tags(trim($input));
    
    // Remove referências à TVOne (case-insensitive)
    $clean = preg_replace('/tvone/i', '', $clean);
    
    // Remove espaços duplos que podem ter surgido da remoção
    $clean = preg_replace('/\s+/', ' ', $clean);
    
    // Retorna o texto limpo sem converter aspas para entidades HTML
    // Isso evita o problema de &quot; aparecer no site
    return trim($clean);
}

/**
 * Valida URL
 * @param string $url URL a ser validada
 * @return bool Verdadeiro se URL válida
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Valida nome de canal (Twitch/Kick)
 * @param string $channel Nome do canal
 * @return bool Verdadeiro se válido
 */
function isValidChannel($channel) {
    // Apenas letras, números, underscore e hífen
    return preg_match('/^[a-zA-Z0-9_-]+$/', $channel) === 1;
}

/**
 * Retorna mensagem de erro formatada
 * @param string $message Mensagem de erro
 * @return string HTML da mensagem de erro
 */
function getErrorMessage($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message) . '</div>';
}

/**
 * Retorna mensagem de sucesso formatada
 * @param string $message Mensagem de sucesso
 * @return string HTML da mensagem de sucesso
 */
function getSuccessMessage($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Gera token CSRF
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 * @param string $token Token a ser validado
 * @return bool Verdadeiro se token válido
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retorna o campo CSRF como HTML
 * @return string HTML do campo CSRF
 */
function getCSRFField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Registra atividade no log
 * @param string $action Ação realizada
 * @param string $details Detalhes da ação
 */
function logActivity($action, $details = '') {
    $logFile = __DIR__ . '/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $logEntry = "[$timestamp] IP: $ip | Action: $action | Details: $details\n";
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Retorna informações do servidor
 * @return array Informações do servidor
 */
function getServerInfo() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'os' => php_uname(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ];
}

/**
 * Verifica se o arquivo de configuração é gravável
 * @return bool Verdadeiro se gravável
 */
function isConfigWritable() {
    if (file_exists(CONFIG_FILE)) {
        return is_writable(CONFIG_FILE);
    }
    return is_writable(dirname(CONFIG_FILE));
}

/**
 * Retorna o tempo decorrido desde o login
 * @return string Tempo formatado
 */
function getSessionDuration() {
    if (!isset($_SESSION['login_time'])) {
        return 'N/A';
    }
    
    $elapsed = time() - $_SESSION['login_time'];
    $hours = floor($elapsed / 3600);
    $minutes = floor(($elapsed % 3600) / 60);
    
    if ($hours > 0) {
        return "{$hours}h {$minutes}m";
    }
    return "{$minutes}m";
}

/**
 * Retorna a próxima hora de expiração da sessão
 * @return string Hora formatada
 */
function getSessionExpireTime() {
    if (!isset($_SESSION['last_activity'])) {
        return 'N/A';
    }
    
    $expireTime = $_SESSION['last_activity'] + SESSION_TIMEOUT;
    return date('H:i:s', $expireTime);
}

/**
 * Valida cor hexadecimal
 * @param string $color Cor em formato hexadecimal
 * @return bool Verdadeiro se cor válida
 */
function isValidHexColor($color) {
    return preg_match('/^#[0-9A-F]{6}$/i', $color) === 1;
}

/**
 * Retorna array de temas pré-definidos
 * @return array Temas disponíveis
 */
function getAvailableThemes() {
    return [
        'dark' => [
            'name' => 'Dark (Padrão)',
            'primary' => '#53fc18',
            'secondary' => '#9146FF',
            'background' => '#0f0f0f',
            'sidebar' => '#181818'
        ],
        'neon' => [
            'name' => 'Neon',
            'primary' => '#00ff00',
            'secondary' => '#ff00ff',
            'background' => '#0a0a0a',
            'sidebar' => '#1a1a1a'
        ],
        'ocean' => [
            'name' => 'Ocean',
            'primary' => '#00d4ff',
            'secondary' => '#0099cc',
            'background' => '#0a1628',
            'sidebar' => '#0f2847'
        ]
    ];
}
?>
