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
// CORREÇÃO: A coluna na sua tabela é `username`, não `nome`.
$stmt = $pdo->prepare("SELECT username, email, foto_perfil, xp FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user) {
    // CORREÇÃO: Utilizar a coluna `username` para definir a sessão.
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['foto_perfil'] = $user['foto_perfil'];
    $_SESSION['xp'] = $user['xp'];
}

// Obtém os dados do utilizador da sessão para o layout
$nomeUsuario = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$emailUsuario = htmlspecialchars($_SESSION['email'] ?? 'email@exemplo.com');
$fotoAtual = $_SESSION['foto_perfil'] ?? null;
$fotoPerfil = ($fotoAtual && file_exists($fotoAtual)) ? htmlspecialchars($fotoAtual) : "https://i.pravatar.cc/150?u=" . urlencode($emailUsuario);
$xpAtualUsuario = $_SESSION['xp'] ?? 0;

// Array de recompensas disponíveis
$recompensas = [
    ["titulo" => "Café Grátis", "descricao" => "Um café de sua escolha na cafetaria parceira.", "xp" => 100, "icon" => "bi-cup-hot-fill"],
    ["titulo" => "Dia de Folga", "descricao" => "Um dia livre para relaxar e recarregar as energias.", "xp" => 2500, "icon" => "bi-beach"],
    ["titulo" => "Gift Card R$50", "descricao" => "Vale-presente de R$ 50 para usar como quiser.", "xp" => 500, "icon" => "bi-gift-fill"],
    ["titulo" => "Almoço Especial", "descricao" => "Vale-refeição para um restaurante local.", "xp" => 300, "icon" => "bi-egg-fried"],
    ["titulo" => "Vale-Cinema", "descricao" => "Ingresso para uma sessão de cinema 2D.", "xp" => 200, "icon" => "bi-film"],
    ["titulo" => "Kit Relaxamento", "descricao" => "Inclui vela aromática e máscara para os olhos.", "xp" => 400, "icon" => "bi-universal-access-circle"],
    ["titulo" => "Assinatura Netflix", "descricao" => "1 mês de assinatura do plano básico.", "xp" => 800, "icon" => "bi-tv-fill"],
    ["titulo" => "Fone Bluetooth", "descricao" => "Fone sem fios para o seu dia a dia.", "xp" => 1000, "icon" => "bi-earbuds"],
    ["titulo" => "Agenda Personalizada", "descricao" => "Organize a sua vida com estilo.", "xp" => 270, "icon" => "bi-book-half"],
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recompensas - MetaXP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
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
            --success-color: #1cc88a;
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

        .xp-balance-card {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            color: white;
            text-align: center;
        }
        .xp-balance-card .label { font-size: 1rem; opacity: 0.8; margin-bottom: 0.5rem; }
        .xp-balance-card .value { font-size: 2.5rem; font-weight: 700; }
        
        .recompensas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .recompensa-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex; flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .recompensa-card:not([disabled]):hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .card-content { padding: 1.5rem; flex-grow: 1; text-align: center; }
        .recompensa-icon { font-size: 3rem; color: var(--primary); margin-bottom: 1rem; }
        .recompensa-card h5 { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .recompensa-card p { color: var(--grey); font-size: 0.9rem; margin-bottom: 1rem; }
        
        .card-footer {
            padding: 1.5rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn-resgatar {
            width: 100%;
            padding: 0.7rem 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            border: none;
            cursor: pointer;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            background-size: 200%;
            color: white;
            transition: all 0.4s ease;
        }
         .btn-resgatar:hover:not(:disabled) {
            background-position: right;
            box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3);
        }

        .btn-resgatar:disabled {
            background: #e9ecef;
            color: var(--grey);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-resgatado {
            background: var(--success-color);
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
                <a href="conquistas.php" title="Conquistas">
                    <span class="nav-icon"><i class="bi bi-trophy-fill"></i></span>
                    <span class="nav-text">Conquistas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="recompensas.php" class="active" title="Recompensas">
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
                <h1>Loja de Recompensas</h1>
                <p>Use o seu XP para resgatar prémios incríveis!</p>
            </header>

            <div class="xp-balance-card">
                <div class="label">O seu saldo de XP</div>
                <div class="value" id="xp-balance"><?= number_format($xpAtualUsuario, 0, ',', '.') ?></div>
            </div>

            <div class="recompensas-grid">
                <?php foreach ($recompensas as $rec): ?>
                    <?php $podeResgatar = $xpAtualUsuario >= $rec['xp']; ?>
                    <div class="recompensa-card">
                        <div class="card-content">
                            <div class="recompensa-icon">
                                <i class="bi <?= htmlspecialchars($rec["icon"]) ?>"></i>
                            </div>
                            <h5><?= htmlspecialchars($rec["titulo"]) ?></h5>
                            <p><?= htmlspecialchars($rec["descricao"]) ?></p>
                        </div>
                        <div class="card-footer">
                            <button class="btn-resgatar" data-cost="<?= $rec['xp'] ?>" <?= !$podeResgatar ? 'disabled' : '' ?>>
                                Resgatar por <?= $rec["xp"] ?> XP
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // --- LÓGICA DE RESGATE DE RECOMPENSA E CONFETE ---
        if (document.querySelectorAll('.btn-resgatar').length > 0) {
            function launchConfetti(origin) {
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: origin,
                    colors: ['#1D84B5', '#4BC6B1', '#FFFFFF']
                });
            }

            const redeemButtons = document.querySelectorAll('.btn-resgatar');
            const xpBalanceElement = document.getElementById('xp-balance');
            let currentUserXP = parseInt(xpBalanceElement.textContent.replace(/\./g, ''));

            redeemButtons.forEach(button => {
                button.addEventListener('click', (event) => {
                    if (button.disabled) {
                        return;
                    }

                    const cost = parseInt(button.dataset.cost);

                    // Simulação de chamada de API e atualização do XP
                    // (Aqui você faria a chamada fetch para o seu back-end)
                    currentUserXP -= cost;
                    xpBalanceElement.textContent = currentUserXP.toLocaleString('pt-BR');

                    // Feedback visual no botão
                    button.textContent = 'Resgatado!';
                    button.classList.add('btn-resgatado');
                    button.disabled = true;
                    
                    // Dispara o confete a partir da posição do botão
                    const rect = event.target.getBoundingClientRect();
                    const origin = {
                        x: (rect.left + rect.right) / 2 / window.innerWidth,
                        y: (rect.top + rect.bottom) / 2 / window.innerHeight
                    };
                    launchConfetti(origin);
                    
                    // Desativa outros botões que o utilizador já não pode pagar
                    redeemButtons.forEach(btn => {
                        if (!btn.disabled) {
                            const btnCost = parseInt(btn.dataset.cost);
                            if (currentUserXP < btnCost) {
                                btn.disabled = true;
                            }
                        }
                    });
                });
            });
        }
    </script>
</body>
</html>

