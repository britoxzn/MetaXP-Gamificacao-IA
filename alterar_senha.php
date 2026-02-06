<?php
session_start();

// Se o utilizador não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Configurações do DB
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_NAME', 'if0_38657243_plataforma_de_planejamento_metas_pessoais');
define('DB_USER', 'if0_38657243');
define('DB_PASS', 'metaspessoais');
define('DB_CHARSET', 'utf8mb4');

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
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Processamento do Formulário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // 1. Validação dos dados
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $errors[] = 'Todos os campos são obrigatórios.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $errors[] = 'A nova senha e a confirmação não coincidem.';
    } elseif (strlen($nova_senha) < 8) {
        $errors[] = 'A nova senha deve ter no mínimo 8 caracteres.';
    }

    // 2. Se não houver erros de validação, processa
    if (empty($errors)) {
        try {
            // Verifica a senha atual
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_atual, $user['password'])) {
                // Se a senha atual estiver correta, atualiza para a nova
                $hashed_password = password_hash($nova_senha, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);

                $success_message = "Senha alterada com sucesso!";
            } else {
                $errors[] = 'A senha atual está incorreta.';
            }

        } catch (PDOException $e) {
            $errors[] = "Erro ao alterar a senha: " . $e->getMessage();
        }
    }
}


// Obtém os dados do utilizador da sessão para o layout
$nomeUsuario = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$emailUsuario = htmlspecialchars($_SESSION['email'] ?? 'email@exemplo.com');
$fotoAtual = $_SESSION['foto_perfil'] ?? null;
$fotoPerfil = ($fotoAtual && file_exists($fotoAtual)) ? htmlspecialchars($fotoAtual) : "https://i.pravatar.cc/150?u=" . urlencode($emailUsuario);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - MetaXP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --grey: #8a95a5;
            --danger: #dc3545;
            --success: #1cc88a;
            --bg-color: #f4f7fc;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-color: #34495e;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --sidebar-width: 260px;
            --sidebar-width-collapsed: 80px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0 0 0 var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }
        body.sidebar-collapsed { margin-left: var(--sidebar-width-collapsed); }

        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex; flex-direction: column;
            transition: width 0.3s ease;
            z-index: 100;
        }
        body.sidebar-collapsed .sidebar { width: var(--sidebar-width-collapsed); align-items: center; }

        .logo { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin-bottom: 2.5rem; white-space: nowrap; text-align: center; }
        .logo span { color: var(--secondary); }
        body.sidebar-collapsed .logo .text { display: none; }
        
        .sidebar-nav { flex-grow: 1; list-style: none; padding: 0; margin: 0; }
        .nav-item a { display: flex; align-items: center; padding: 0.9rem 1rem; color: var(--grey); text-decoration: none; border-radius: 8px; margin-bottom: 0.5rem; transition: all 0.3s ease; white-space: nowrap; }
        .nav-item a:hover, .nav-item a.active { background-color: #eef5ff; color: var(--primary); font-weight: 500; }
        .nav-icon { font-size: 1.2rem; min-width: 24px; margin-right: 1.5rem; transition: margin-right 0.3s ease; }
        body.sidebar-collapsed .nav-icon { margin-right: 0; }
        .nav-text { opacity: 1; transition: opacity 0.2s ease; }
        body.sidebar-collapsed .nav-text { opacity: 0; width: 0; overflow: hidden; }

        .main-wrapper { display: flex; flex-direction: column; min-height: 100vh; }

        .top-navbar {
            background-color: var(--card-bg);
            padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: var(--shadow);
            position: sticky; top: 0; z-index: 99;
        }
        
        #toggle-sidebar { background: transparent; border: none; cursor: pointer; font-size: 1.5rem; color: var(--grey); }
        .navbar-right { display: flex; align-items: center; gap: 1.5rem; }
        .user-menu { position: relative; }
        .user-menu > img { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }
        .main-content { padding: 2rem; flex-grow: 1; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }

        .btn-primary {
            background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%;
            color: white; border: none; border-radius: 8px; font-weight: 500;
            padding: 0.7rem 1.5rem;
            transition: all 0.4s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer;
        }
        .btn-primary:hover { background-position: right; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3); }
        
        .btn-secondary {
            background-color: #e9ecef;
            color: var(--grey);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.7rem 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-secondary:hover { background-color: #d8dde3; }

        .form-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-label { font-weight: 500; margin-bottom: 0.5rem; display: block; }
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(29, 132, 181, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            top: 70%;
            right: 1rem;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--grey);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-danger { background-color: rgba(220, 53, 69, 0.1); border-left: 5px solid var(--danger); }
        .alert-success { background-color: rgba(28, 200, 138, 0.1); border-left: 5px solid var(--success); color: #0c6e53;}

    </style>
</head>
<body class="sidebar-collapsed">

    <aside class="sidebar">
        <div class="logo">M<span><span class="text">XP</span></span></div>
        <ul class="sidebar-nav">
             <li class="nav-item">
                <a href="dashboard.php" title="Dashboard">
                    <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="ver_mais_metas.php" title="Minhas Metas">
                    <span class="nav-icon"><i class="bi bi-check2-circle"></i></span>
                    <span class="nav-text">Minhas Metas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="conquistas.php" title="Conquistas">
                    <span class="nav-icon"><i class="bi bi-trophy-fill"></i></span>
                    <span class="nav-text">Conquistas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="recompensas.php" title="Recompensas">
                    <span class="nav-icon"><i class="bi bi-gift-fill"></i></span>
                    <span class="nav-text">Recompensas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" title="Logout">
                    <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <nav class="top-navbar">
            <button id="toggle-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="navbar-right">
                <div class="user-menu">
                    <img src="<?= $fotoPerfil ?>" alt="Foto do Perfil" title="<?= $nomeUsuario ?>">
                </div>
            </div>
        </nav>
        
        <main class="main-content">
            <header class="header">
                <h1>Alterar Senha</h1>
            </header>
            
            <div class="form-card">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <p class="mb-0"><?= htmlspecialchars($success_message) ?></p>
                    </div>
                <?php endif; ?>

                <form action="alterar_senha.php" method="POST">
                    <div class="form-group">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                        <span class="password-toggle"><i class="bi bi-eye-slash"></i></span>
                    </div>
                    <div class="form-group">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" class="form-control" required minlength="8">
                        <span class="password-toggle"><i class="bi bi-eye-slash"></i></span>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required minlength="8">
                        <span class="password-toggle"><i class="bi bi-eye-slash"></i></span>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="dashboard.php" class="btn-secondary">Voltar</a>
                        <button type="submit" class="btn-primary">Salvar Nova Senha</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Lógica para mostrar/ocultar senha
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const input = toggle.previousElementSibling;
                const icon = toggle.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            });
        });
    </script>
</body>
</html>