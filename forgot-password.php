<?php
session_start();

// Configurações do ambiente
define('SITE_NAME', 'MetaXP');
define('SITE_URL', 'https://plataforma.metaxp.com'); // Mude para o seu URL real
define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');

// Configurações do banco de dados
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_NAME', 'if0_38657243_plataforma_de_planejamento_metas_pessoais');
define('DB_USER', 'if0_38657243');
define('DB_PASS', 'metaspessoais');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set(DEFAULT_TIMEZONE);

// Conexão com o banco de dados
$pdo = null;
try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    die("Desculpe, estamos com problemas técnicos. Por favor, tente novamente mais tarde.");
}

/*
NOTA IMPORTANTE: Para esta página funcionar, precisa de criar a tabela de tokens na sua base de dados.
Execute o seguinte comando SQL:

CREATE TABLE IF NOT EXISTS `tokens_recuperacao` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  INDEX `token_idx` (`token`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);

*/

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Por favor, insira um e-mail válido.";
        $mensagem_tipo = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Gera um token seguro
                $token = bin2hex(random_bytes(32));
                // O link de redefinição expirará em 1 hora
                $expira_em = date('Y-m-d H:i:s', time() + 3600); 

                // Armazena o token na base de dados
                $sql = "INSERT INTO tokens_recuperacao (usuario_id, token, expira_em) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['id'], $token, $expira_em]);

                // Simulação do envio de e-mail (em um projeto real, use uma biblioteca como PHPMailer)
                $link_recuperacao = SITE_URL . "/reset-password.php?token=" . $token;
                
                // Para depuração, vamos guardar o link na sessão. Remova isto em produção.
                $_SESSION['debug_reset_link'] = $link_recuperacao; 
            }
            
            // Por segurança, mostramos sempre a mesma mensagem de sucesso,
            // quer o e-mail exista ou não, para evitar enumeração de usuários.
            $mensagem = "Se um conta com este e-mail existir, um link de recuperação foi enviado.";
            $mensagem_tipo = 'success';

        } catch (PDOException $e) {
            error_log("Erro na recuperação de senha: " . $e->getMessage());
            $mensagem = "Ocorreu um erro no servidor. Tente novamente.";
            $mensagem_tipo = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --light: #F8F9FA;
            --dark: #212529;
            --danger: #DC3545;
            --success: #1cc88a;
            --grey: #6c757d;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite linear;
        }
        body::before { width: 30vw; height: 30vw; bottom: -15vw; left: -15vw; }
        body::after { width: 40vw; height: 40vw; top: -20vw; right: -20vw; animation-duration: 25s; animation-delay: -5s; }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-100vh) rotate(360deg); }
        }
        .container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.6s ease-out;
            z-index: 1;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .logo { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary); }
        .logo span { color: var(--secondary); text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .subtitle { font-size: 1rem; color: var(--grey); margin-bottom: 2rem; font-weight: 400; max-width: 300px; margin-left: auto; margin-right: auto; }
        .form-group { position: relative; margin-bottom: 2rem; }
        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: var(--light);
            transition: all 0.3s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(29, 132, 181, 0.2);
        }
        .form-label {
            position: absolute;
            top: 50%;
            left: 3.5rem;
            transform: translateY(-50%);
            color: var(--grey);
            pointer-events: none;
            transition: all 0.3s;
        }
        .form-input:focus ~ .form-label,
        .form-input:not(:placeholder-shown) ~ .form-label {
            top: -10px; left: 10px; font-size: 0.8rem; background: white; padding: 0 5px; color: var(--primary);
        }
        .form-group .icon {
            position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); color: var(--grey); transition: color 0.3s;
        }
        .form-input:focus ~ .icon { color: var(--primary); }
        .submit-btn {
            width: 100%; padding: 1rem; background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.4s;
        }
        .submit-btn:hover { background-position: right; transform: translateY(-3px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.4); }
        .back-link { margin-top: 1.5rem; font-size: 0.9rem; }
        .back-link a { color: var(--primary); text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .back-link a:hover { text-decoration: underline; }
        
        .alert {
            padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; color: var(--dark); text-align: left;
        }
        .alert-danger { background-color: rgba(220, 53, 69, 0.1); border-left: 5px solid var(--danger); }
        .alert-success { background-color: rgba(28, 200, 138, 0.1); border-left: 5px solid var(--success); }

        @media (max-width: 480px) {
            .container { padding: 2rem 1.5rem; margin: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Meta<span>XP</span></div>
        <p class="subtitle">Insira o seu e-mail para enviarmos um link de recuperação.</p>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($mensagem_tipo) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php 
            // Para fins de depuração, mostra o link gerado. REMOVA EM PRODUÇÃO.
            if (isset($_SESSION['debug_reset_link'])) {
                echo '<div class="alert alert-success"><strong>Link de Debug:</strong> <a href="' . $_SESSION['debug_reset_link'] . '">Clique aqui para redefinir</a></div>';
                unset($_SESSION['debug_reset_link']);
            }
        ?>

        <form method="POST" action="forgot-password.php" novalidate>
            <div class="form-group">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>
                </span>
                <input type="email" id="email" name="email" class="form-input" placeholder=" " required>
                <label for="email" class="form-label">Seu e-mail</label>
            </div>
            
            <button type="submit" class="submit-btn">Enviar Link de Recuperação</button>
        </form>

        <div class="back-link">
            <a href="login.php">Lembrou a senha? Voltar para o login</a>
        </div>
    </div>
</body>
</html>
