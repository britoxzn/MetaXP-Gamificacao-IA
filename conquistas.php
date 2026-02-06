<?php
session_start();
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

// Busca os dados mais recentes do utilizador para garantir que a sessão esteja atualizada
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, foto_perfil FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['foto_perfil'] = $user['foto_perfil'];
}

// Obtém os dados do utilizador da sessão para o layout
$nomeUsuario = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$emailUsuario = htmlspecialchars($_SESSION['email'] ?? 'email@exemplo.com');
$fotoAtual = $_SESSION['foto_perfil'] ?? null;
$fotoPerfil = ($fotoAtual && file_exists($fotoAtual)) ? htmlspecialchars($fotoAtual) : "https://i.pravatar.cc/150?u=" . urlencode($emailUsuario);

// Simulando conquistas
$conquistas = [
    ["titulo" => "Primeira Meta", "descricao" => "Concluiu a sua primeira meta!", "xp" => 50, "icon" => "bi-check-lg"],
    ["titulo" => "5 Metas Concluídas", "descricao" => "Completou 5 metas com sucesso!", "xp" => 150, "icon" => "bi-check2-all"],
    ["titulo" => "Maratona", "descricao" => "Completou metas por 7 dias seguidos!", "xp" => 200, "icon" => "bi-calendar-week"],
    ["titulo" => "Decano", "descricao" => "Está ativo há 30 dias!", "xp" => 300, "icon" => "bi-calendar3"],
    ["titulo" => "Persistente", "descricao" => "Concluiu 10 metas no mês!", "xp" => 250, "icon" => "bi-trophy"],
    ["titulo" => "Focado", "descricao" => "Cumpriu metas 3 dias seguidos!", "xp" => 100, "icon" => "bi-bullseye"],
    ["titulo" => "Produtivo", "descricao" => "Finalizou 20 metas no total!", "xp" => 400, "icon" => "bi-graph-up-arrow"],
    ["titulo" => "Mestre do Planeamento", "descricao" => "Planeou 5 metas futuras!", "xp" => 180, "icon" => "bi-journal-check"],
    ["titulo" => "Velocista", "descricao" => "Terminou uma meta em menos de 1 dia!", "xp" => 120, "icon" => "bi-lightning-charge"],
    ["titulo" => "Conquistador", "descricao" => "Alcançou 50 metas concluídas!", "xp" => 600, "icon" => "bi-award"],
    ["titulo" => "Motivado", "descricao" => "Entrou 7 dias seguidos para ver as suas metas!", "xp" => 90, "icon" => "bi-door-open"],
    ["titulo" => "Organizado", "descricao" => "Utilizou categorias em 10 metas!", "xp" => 110, "icon" => "bi-tags"],
];

$conquistasDesbloqueadas = 5;
$totalConquistas = count($conquistas);
$progressoConquistas = ($totalConquistas > 0) ? ($conquistasDesbloqueadas / $totalConquistas) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conquistas - MetaXP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --grey: #8a95a5;
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
        .user-menu > img {
            width: 40px; height: 40px; border-radius: 50%;
            cursor: pointer; border: 2px solid transparent;
            transition: border-color 0.3s ease; object-fit: cover;
        }
        .user-menu:hover > img { border-color: var(--primary); }

        .dropdown-menu {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: 240px; background-color: var(--card-bg);
            border-radius: 12px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0; z-index: 1000;
            opacity: 0; visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        }
        .user-menu:hover .dropdown-menu {
            opacity: 1; visibility: visible; transform: translateY(0);
        }

        .dropdown-header {
            padding: 1rem 1.5rem; border-bottom: 1px solid #eee; margin-bottom: 0.5rem;
        }
        .dropdown-header strong { font-weight: 600; color: var(--dark); }
        .dropdown-header small { color: var(--grey); font-size: 0.8rem; word-break: break-all; }
        
        .dropdown-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1.5rem; color: var(--text-color);
            text-decoration: none; font-size: 0.9rem;
        }
        .dropdown-item:hover { background-color: #eef5ff; color: var(--primary); }
        .dropdown-item i { font-size: 1.1rem; }
        
        .main-content { padding: 2rem; flex-grow: 1; }

        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }
        .header p { color: var(--grey); }

        .progress-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .progress-bar-container { background-color: #e9ecef; border-radius: 30px; height: 10px; overflow: hidden; margin-top: 0.5rem; }
        .progress-bar { height: 100%; width: 0; background: linear-gradient(90deg, var(--secondary), var(--primary)); border-radius: 30px; }
        
        .conquistas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .conquista-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .conquista-card.locked {
            opacity: 0.6;
            filter: grayscale(80%);
        }
        .conquista-card:not(.locked):hover {
            transform: translateY(-5px);
        }

        .conquista-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .conquista-card.locked .conquista-icon {
            color: var(--grey);
        }

        .conquista-card h5 { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .conquista-card p { color: var(--grey); font-size: 0.9rem; margin-bottom: 1rem; }

        .xp-badge {
            background-color: rgba(29, 132, 181, 0.1);
            color: var(--primary);
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            display: inline-block;
        }
        .conquista-card.locked .xp-badge {
             background-color: #e9ecef;
             color: var(--grey);
        }
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
                <a href="conquistas.php" class="active" title="Conquistas">
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
                <a href="ranking.php" title="Ranking">
                    <span class="nav-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                    <span class="nav-text">Ranking</span>
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
                    <img src="<?= $fotoPerfil ?>" alt="Foto do Perfil">
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <strong><?= $nomeUsuario ?></strong><br>
                            <small><?= $emailUsuario ?></small>
                        </div>
                        <a href="editar_perfil.php" class="dropdown-item">
                            <i class="bi bi-person-fill"></i> Editar Perfil
                        </a>
                        <a href="alterar_senha.php" class="dropdown-item">
                            <i class="bi bi-key-fill"></i> Alterar Senha
                        </a>
                        <a href="logout.php" class="dropdown-item">
                           <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        
        <main class="main-content">
            <header class="header">
                <h1>Minhas Conquistas</h1>
                <p>Cada emblema representa um passo na sua jornada de sucesso.</p>
            </header>

            <div class="progress-card">
                <div class="d-flex justify-content-between">
                    <strong>Progresso Total</strong>
                    <span><?= $conquistasDesbloqueadas ?> / <?= $totalConquistas ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?= $progressoConquistas ?>%;"></div>
                </div>
            </div>

            <div class="conquistas-grid">
                <?php foreach ($conquistas as $index => $c): ?>
                    <?php $isLocked = $index >= $conquistasDesbloqueadas; ?>
                    <div class="conquista-card <?= $isLocked ? 'locked' : '' ?>">
                        <div class="conquista-icon">
                            <i class="bi <?= htmlspecialchars($c["icon"]) ?>"></i>
                        </div>
                        <h5><?= htmlspecialchars($c["titulo"]) ?></h5>
                        <p><?= htmlspecialchars($c["descricao"]) ?></p>
                        <span class="xp-badge">+<?= $c["xp"] ?> XP</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>

