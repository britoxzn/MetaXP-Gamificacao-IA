<?php
session_start();

// Se o utilizador não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Configurações do ambiente e DB
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
$metas = [];

try {
    // Consulta para obter as metas do usuário logado
    $stmt = $pdo->prepare("SELECT id, titulo, descricao, status, prazo FROM metas WHERE user_id = ? ORDER BY prazo ASC");
    $stmt->execute([$user_id]);
    $metas = $stmt->fetchAll();
} catch (PDOException $e) {
    // Trata o erro de consulta
    die("Erro ao buscar as metas: " . $e->getMessage());
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
    <title>Minhas Metas - MetaXP</title>
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
            
            --status-pendente: #ffc107;
            --status-em_andamento: #0dcaf0;
            --status-concluida: #1cc88a;
            --status-atrasada: #dc3545;
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
        
        /* Dropdown Menu Styles */
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

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }

        .btn-primary {
            background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%;
            color: white; border: none; border-radius: 8px; font-weight: 500;
            padding: 0.6rem 1.2rem;
            transition: all 0.4s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { background-position: right; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3); }

        .filters { margin-bottom: 1.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filter-btn {
            background-color: var(--card-bg);
            border: 1px solid #ddd;
            color: var(--grey);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .filter-btn:hover { background-color: #e9ecef; }
        .filter-btn.active { background-color: var(--primary); color: white; border-color: var(--primary); }
        
        .metas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .meta-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex; flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .meta-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        
        .card-content { padding: 1.5rem; flex-grow: 1;}
        .card-content h5 { font-weight: 600; margin-bottom: 0.5rem; }
        .card-content p { color: var(--grey); font-size: 0.9rem; margin-bottom: 1rem; }
        
        .card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .status-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            color: white;
            text-transform: capitalize;
        }
        .status-pendente { background-color: var(--status-pendente); }
        .status-em_andamento { background-color: var(--status-em_andamento); }
        .status-concluida { background-color: var(--status-concluida); }
        .status-atrasada { background-color: var(--status-atrasada); }

        .btn-edit {
            color: var(--grey);
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        .btn-edit:hover { color: var(--primary); }

        .no-metas { text-align: center; padding: 4rem; background: var(--card-bg); border-radius: 16px; box-shadow: var(--shadow); }
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
                <a href="ver_mais_metas.php" class="active" title="Minhas Metas">
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
                <!-- ADICIONADO: Menu dropdown do utilizador -->
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
                <h1>Minhas Metas</h1>
                <a href="nova_meta.php" class="btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Nova Meta
                </a>
            </header>

            <div class="filters">
                <button class="filter-btn active" data-filter="all">Todas</button>
                <button class="filter-btn" data-filter="pendente">Pendentes</button>
                <button class="filter-btn" data-filter="em_andamento">Em Andamento</button>
                <button class="filter-btn" data-filter="concluida">Concluídas</button>
            </div>

            <div class="metas-grid">
                <?php if (empty($metas)): ?>
                    <div class="no-metas" style="grid-column: 1 / -1;">
                        <h3>Nenhuma meta encontrada.</h3>
                        <p>Que tal começar a planejar o seu futuro agora?</p>
                        <a href="nova_meta.php" class="btn-primary">Criar Primeira Meta</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($metas as $meta): ?>
                        <?php
                            $status = !empty($meta['status']) ? $meta['status'] : 'pendente';
                        ?>
                        <div class="meta-card" data-status="<?= htmlspecialchars($status) ?>">
                            <div class="card-content">
                                <h5><?= htmlspecialchars($meta['titulo']) ?></h5>
                                <p><?= htmlspecialchars(substr($meta['descricao'], 0, 100)) ?>...</p>
                                <small class="text-muted">Prazo: <?= date('d/m/Y', strtotime($meta['prazo'])) ?></small>
                            </div>
                            <div class="card-footer">
                                <span class="status-badge status-<?= htmlspecialchars($status) ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($status)) ?>
                                </span>
                                <a href="editar_meta.php?id=<?= $meta['id'] ?>" class="btn-edit" title="Editar Meta">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Lógica da Sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Lógica dos Filtros
        const filterButtons = document.querySelectorAll('.filter-btn');
        const metaCards = document.querySelectorAll('.meta-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const filter = button.dataset.filter;

                metaCards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>

