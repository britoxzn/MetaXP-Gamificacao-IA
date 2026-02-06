<?php
/*
--- GUIA DE DEPURAÇÃO (DEBUG GUIDE) ---
Se não estiver a conseguir fazer o login, o problema provavelmente NÃO está neste código, mas sim nos DADOS do seu banco.
Siga estes passos para resolver:

CAUSA MAIS PROVÁVEL: A senha no banco de dados não está encriptada corretamente.
A função `password_verify()` SÓ funciona se o utilizador tiver sido criado através da sua página `register.php`,
que usa `password_hash()` para guardar a senha de forma segura.

SOLUÇÃO PASSO-A-PASSO:
1. Vá ao seu banco de dados (phpMyAdmin) e APAGUE o utilizador com o qual está a tentar fazer login.
2. Abra o seu site e vá para a página `register.php`.
3. CRIE UM NOVO UTILIZADOR através do formulário de registo.
4. Volte para esta página de login e tente entrar com os dados do NOVO utilizador que acabou de criar.

Se ainda assim não funcionar, verifique:
- Se os nomes das colunas no seu banco de dados são exatamente `id`, `username`, `email`, `password`.
- Se o nome da tabela é exatamente `users`.
*/

session_start();

// Configurações do ambiente
define('SITE_NAME', 'MetaXP');
define('SITE_URL', 'https://plataforma.metaxp.com');
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

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors['general'] = "Por favor, preencha o e-mail.";
    } elseif (empty($password)) {
        $errors['general'] = "Por favor, preencha a senha.";
    } else {
        try {
            // CORREÇÃO: Usando a tabela 'users' e as colunas corretas ('username', 'password')
            $sql = "SELECT id, username, email, password FROM users WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // CORREÇÃO: Verificando a senha com a coluna 'password'
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                // CORREÇÃO: Usando a coluna 'username' para a sessão
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $errors['general'] = "E-mail ou senha incorretos!";
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $errors['general'] = "Ocorreu um erro no servidor. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --accent: #FF6584;
            --light: #F8F9FA;
            --dark: #212529;
            --danger: #DC3545;
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
        .login-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 420px;
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
        .subtitle { font-size: 1rem; color: var(--grey); margin-bottom: 2.5rem; font-weight: 400; }
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
            top: -10px;
            left: 10px;
            font-size: 0.8rem;
            background: white;
            padding: 0 5px;
            color: var(--primary);
        }
        .form-group .icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--grey);
            transition: color 0.3s;
        }
        .form-input:focus ~ .icon { color: var(--primary); }
        .password-toggle { position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); cursor: pointer; color: var(--grey); }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            background-size: 200%;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s;
        }
        .submit-btn:hover { background-position: right; transform: translateY(-3px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.4); }
        .links { margin-top: 1.5rem; font-size: 0.9rem; }
        .links a { color: var(--primary); text-decoration: none; margin: 0 0.5rem; transition: all 0.2s; }
        .links a:hover { text-decoration: underline; }
        .links span { color: var(--grey); }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 5px solid var(--danger);
            color: var(--dark);
            text-align: left;
        }
        @media (max-width: 480px) {
            .login-container { padding: 2rem 1.5rem; margin: 1rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">Meta<span>XP</span></div>
        <p class="subtitle">Entre e comece a alcançar suas metas</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>
                </span>
                <input type="email" id="email" name="email" class="form-input" placeholder=" " required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <label for="email" class="form-label">Seu e-mail</label>
            </div>
            
            <div class="form-group">
                <span class="icon">
                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                </span>
                <input type="password" id="password" name="password" class="form-input" placeholder=" " required>
                <label for="password" class="form-label">Sua senha</label>
                <span class="password-toggle" id="togglePassword">
                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" id="eye-icon"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                </span>
            </div>

            <button type="submit" class="submit-btn">Entrar</button>
        </form>

        <div class="links">
            <a href="register.php">Criar conta</a>
            <span>|</span>
            <a href="forgot-password.php">Esqueci a senha</a>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eye-icon');
        const eyeIconPath = "M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z";
        const eyeSlashIconPath = "M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.94 5.94 0 0 1 8 5.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.288.822.822.083.083.083.083a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829l.822.822zm-2.943 1.288a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829zm-2.943 1.288a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709z M2.066 2.066.066 4.066 2.066 2.066a1.5 1.5 0 0 1 2.122 0l1.147 1.147-2.122 2.122z";
        let passwordVisible = false;

        togglePassword.addEventListener('click', function (e) {
            passwordVisible = !passwordVisible;
            const type = passwordVisible ? 'text' : 'password';
            password.setAttribute('type', type);
            const newIconPath = passwordVisible ? eyeSlashIconPath : eyeIconPath;
            eyeIcon.querySelector('path').setAttribute('d', newIconPath);
        });
    </script>

</body>
</html>

