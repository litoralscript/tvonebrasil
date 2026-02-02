<?php
/**
 * TV One Portal - Página de Segurança
 */

require_once __DIR__ . '/../config/functions.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$config = loadConfig();
$message = '';
$messageType = '';

// Processa mudança de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = 'Token de segurança inválido.';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'Todos os campos são obrigatórios.';
            $messageType = 'error';
        } elseif (!password_verify($currentPassword, $config['admin_password_hash'])) {
            $message = 'Senha atual incorreta.';
            $messageType = 'error';
            logActivity('PASSWORD_CHANGE_FAILED', 'Tentativa de alteração com senha incorreta');
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'As novas senhas não coincidem.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'A nova senha deve ter pelo menos 6 caracteres.';
            $messageType = 'error';
        } else {
            $config['admin_password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
            if (saveConfig($config)) {
                $message = '✓ Senha alterada com sucesso!';
                $messageType = 'success';
                logActivity('PASSWORD_CHANGED', 'Senha do admin alterada com sucesso');
            } else {
                $message = 'Erro ao salvar a nova senha.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança - Painel de Administração</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --kick-green: <?php echo htmlspecialchars($config['theme_primary_color']); ?>;
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
                    <li><a href="security.php" class="active">Segurança</a></li>
                    <li><a href="logs.php">Logs</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>🔒 Segurança</h1>
                <div>
                    <span style="font-size: 12px; color: var(--text-secondary);">
                        Sessão ativa: <?php echo getSessionDuration(); ?>
                    </span>
                </div>
            </header>

            <div class="admin-content">
                <!-- Mensagens -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <?php if ($messageType === 'success'): ?>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            <?php else: ?>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Alterar Senha -->
                <form method="POST" class="card">
                    <div class="card-header">Alterar Senha</div>
                    <div class="card-body">
                        <div class="form-group required">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" required>
                            <small>Digite sua senha atual para confirmar a alteração</small>
                        </div>

                        <div class="form-group required">
                            <label for="new_password">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <small>Mínimo de 6 caracteres</small>
                        </div>

                        <div class="form-group required">
                            <label for="confirm_password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <small>Digite a mesma senha novamente</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <?php echo getCSRFField(); ?>
                        <button type="submit" class="btn btn-primary">🔑 Alterar Senha</button>
                    </div>
                </form>

                <!-- Informações de Segurança -->
                <div class="card">
                    <div class="card-header">Informações de Segurança</div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Tempo de Sessão</strong>
                                <div><?php echo getSessionDuration(); ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Expira em</strong>
                                <div><?php echo getSessionExpireTime(); ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Seu IP</strong>
                                <div><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recomendações de Segurança -->
                <div class="card">
                    <div class="card-header">🛡️ Recomendações de Segurança</div>
                    <div class="card-body">
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); display: flex; gap: 10px;">
                                <span>✓</span>
                                <div>
                                    <strong>Use uma senha forte</strong>
                                    <div style="font-size: 12px; color: var(--text-secondary);">Combine letras maiúsculas, minúsculas, números e símbolos</div>
                                </div>
                            </li>
                            <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); display: flex; gap: 10px;">
                                <span>✓</span>
                                <div>
                                    <strong>Altere a senha regularmente</strong>
                                    <div style="font-size: 12px; color: var(--text-secondary);">Recomenda-se alterar a cada 30 dias</div>
                                </div>
                            </li>
                            <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); display: flex; gap: 10px;">
                                <span>✓</span>
                                <div>
                                    <strong>Não compartilhe suas credenciais</strong>
                                    <div style="font-size: 12px; color: var(--text-secondary);">Mantenha sua senha em segredo</div>
                                </div>
                            </li>
                            <li style="padding: 10px 0; display: flex; gap: 10px;">
                                <span>✓</span>
                                <div>
                                    <strong>Faça logout quando terminar</strong>
                                    <div style="font-size: 12px; color: var(--text-secondary);">Especialmente em computadores compartilhados</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
