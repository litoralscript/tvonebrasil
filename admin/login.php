<?php
/**
 * TV One Portal - Login do Painel Admin
 */

require_once __DIR__ . '/../config/functions.php';

// Se já está autenticado, redireciona para admin
if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Processa formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $error = 'Por favor, insira a senha.';
    } elseif (adminLogin($password)) {
        logActivity('LOGIN_SUCCESS', 'Admin fez login com sucesso');
        header('Location: index.php');
        exit;
    } else {
        logActivity('LOGIN_FAILED', 'Tentativa de login com senha incorreta');
        $error = 'Senha incorreta. Tente novamente.';
    }
}

$config = loadConfig();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel de Administração</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
        }

        .login-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .login-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--kick-green);
            box-shadow: 0 0 0 3px rgba(83, 252, 24, 0.1);
        }

        .login-button {
            padding: 12px;
            background-color: var(--kick-green);
            color: #000000;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-button:hover {
            background-color: #45d910;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(83, 252, 24, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background-color: rgba(255, 51, 51, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(0, 204, 0, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 12px;
            color: var(--text-tertiary);
        }

        .login-footer a {
            color: var(--kick-green);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .info-box {
            background-color: var(--bg-tertiary);
            border-left: 3px solid var(--kick-green);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: left;
        }

        .info-box strong {
            color: var(--text-primary);
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }

            .login-title {
                font-size: 20px;
            }

            .login-logo {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">🔐</div>
            <h1 class="login-title">Painel Admin</h1>
            <p class="login-subtitle">TV One Portal</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>🔑 Credenciais Padrão:</strong>
                Senha: <code>admin123</code><br>
                <small>Altere a senha após o primeiro login!</small>
            </div>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="password">Senha do Administrador</label>
                    <input type="password" id="password" name="password" placeholder="Digite sua senha" required autofocus>
                </div>

                <button type="submit" class="login-button">Entrar</button>
            </form>

            <div class="login-footer">
                <a href="../">← Voltar ao Portal</a>
            </div>
        </div>
    </div>
</body>
</html>
