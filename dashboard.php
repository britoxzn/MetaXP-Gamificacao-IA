<?php
/*
--- GUIA DE DEPURAÇÃO COMPLETO ---
Se esta página estiver a dar erro, o problema é quase de certeza
uma inconsistência na sua base de dados.

CAUSA MAIS PROVÁVEL: Faltam colunas na sua tabela `users`.
O dashboard precisa das colunas `nivel`, `xp`, e `foto_perfil`.

SOLUÇÃO:
1. Aceda ao seu gestor de base de dados (phpMyAdmin).
2. Selecione a sua base de dados e vá para a secção SQL.
3. Copie e cole o comando SQL abaixo e execute-o:

ALTER TABLE `users`
ADD COLUMN `nivel` INT NOT NULL DEFAULT 1 AFTER `password`,
ADD COLUMN `xp` INT NOT NULL DEFAULT 0 AFTER `nivel`,
ADD COLUMN `foto_perfil` VARCHAR(255) NULL AFTER `xp`;

Após executar este comando, o seu dashboard deverá funcionar.
*/

// Ativar/desativar o modo de depuração
define('DEBUG_MODE', false);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

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
$db_error = null;

try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    $db_error = "Erro de conexão com o banco de dados: " . $e->getMessage();
}

$user = null;
$metas = [];
$ranking_users = [];

if ($pdo) {
    try {
        // Busca os dados mais recentes do utilizador para garantir que a sessão esteja atualizada
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT username, email, foto_perfil, nivel, xp FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            // Atualiza a sessão com os dados mais recentes
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['foto_perfil'] = $user['foto_perfil'];
            $_SESSION['level'] = $user['nivel'];
            $_SESSION['xp'] = $user['xp'];
        }

        // Buscar metas diretamente com PHP
        $stmt_metas = $pdo->prepare("SELECT id, titulo, status, categoria, prazo FROM metas WHERE user_id = ? AND status != 'concluida' ORDER BY prazo ASC LIMIT 3");
        $stmt_metas->execute([$user_id]);
        $metas = $stmt_metas->fetchAll();
        
        // Buscar o ranking para o dashboard
        $stmt_ranking = $pdo->prepare("SELECT id, username, email, xp, foto_perfil FROM users ORDER BY xp DESC LIMIT 5");
        $stmt_ranking->execute();
        $ranking_users = $stmt_ranking->fetchAll();


    } catch (PDOException $e) {
        $db_error = "Erro na consulta ao banco de dados: " . $e->getMessage();
    }
}


// Obtém os dados do utilizador da sessão com valores padrão
$nomeUsuario = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$emailUsuario = htmlspecialchars($_SESSION['email'] ?? 'email@exemplo.com');
$fotoAtual = $_SESSION['foto_perfil'] ?? null;

// Define a foto de perfil (se existir e o ficheiro for válido) ou usa a padrão
$fotoPerfil = ($fotoAtual && file_exists($fotoAtual)) 
    ? htmlspecialchars($fotoAtual) 
    : "https://i.pravatar.cc/150?u=" . urlencode($emailUsuario);

// Dados de gamificação (com valores padrão)
$nivel = $_SESSION['level'] ?? 1;
$xp = $_SESSION['xp'] ?? 0;
$metasConcluidas = 12; // Exemplo de dado que viria do banco
$xpParaProximoNivel = $nivel * 100; 
$progressoXP = ($xpParaProximoNivel > 0) ? round(($xp / $xpParaProximoNivel) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MetaXP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --accent: #FF6584;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --grey: #8a95a5;
            --danger: #dc3545;
            --bg-color: #f4f7fc;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-color: #34495e;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --sidebar-width: 260px;
            --sidebar-width-collapsed: 80px;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0 0 0 var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        body.sidebar-collapsed {
            margin-left: var(--sidebar-width-collapsed);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            z-index: 100;
        }
        
        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-width-collapsed);
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2.5rem;
            white-space: nowrap;
            text-align: center;
        }
        .logo span {
            color: var(--secondary);
        }
        body.sidebar-collapsed .logo .text {
            display: none;
        }
        
        .sidebar-nav {
            flex-grow: 1;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.9rem 1rem;
            color: var(--grey);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-item a:hover, .nav-item a.active {
            background-color: #eef5ff;
            color: var(--primary);
            font-weight: 500;
        }

        .nav-icon {
            font-size: 1.2rem;
            min-width: 24px;
            margin-right: 1.5rem;
            transition: margin-right 0.3s ease;
        }
        body.sidebar-collapsed .nav-icon {
            margin-right: 0;
        }
        
        .nav-text {
            opacity: 1;
            transition: opacity 0.2s ease;
        }
        body.sidebar-collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-navbar {
            background-color: var(--card-bg);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        #toggle-sidebar {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--grey);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-menu {
            position: relative;
        }

        .user-menu > img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
            object-fit: cover;
        }
        .user-menu:hover > img {
            border-color: var(--primary);
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 240px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        }
        .user-menu:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            margin-bottom: 0.5rem;
        }
        .dropdown-header strong { font-weight: 600; color: var(--dark); }
        .dropdown-header small { color: var(--grey); font-size: 0.8rem; word-break: break-all; }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .dropdown-item:hover {
            background-color: #eef5ff;
            color: var(--primary);
        }
        .dropdown-item i { font-size: 1.1rem; }

        .main-content {
            padding: 2rem;
            flex-grow: 1;
        }

        .header {
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        .header p {
            color: var(--grey);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            background-size: 200%;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            transition: all 0.4s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover {
            background-position: right;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .stat-card .icon {
            font-size: 2rem;
            padding: 0.8rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
        }
        .stat-card .icon.level { background-color: #ffc10720; color: #ffc107; }
        .stat-card .icon.xp { background-color: #1cc88a20; color: #1cc88a; }
        .stat-card .icon.goals { background-color: #4e73df20; color: #4e73df; }
        .stat-card .value { font-size: 1.5rem; font-weight: 600; }
        .stat-card .label { font-size: 0.9rem; color: var(--grey); }

        /* --- CORREÇÃO DO LAYOUT DA GRELHA --- */
        .dashboard-grid {
            display: grid;
            /* Grelha de 2 colunas: a principal (2fr) é duas vezes maior que a lateral (1fr) */
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        .main-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .side-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        /* --- FIM DA CORREÇÃO DA GRELHA --- */


        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        .card-header {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            color: var(--dark);
        }
        .card-header .icon {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .xp-progress {
            background-color: #e9ecef;
            border-radius: 30px;
            height: 8px;
            overflow: hidden;
        }
        .xp-progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            border-radius: 30px;
        }
        
        .meta-progress-item {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
         #lista-metas .meta-progress-item:last-child { border-bottom: none; padding-bottom: 0; }
         #lista-metas .meta-progress-item:first-child { padding-top: 0; }
         
        .meta-info strong { display: block; font-weight: 500; }
        .meta-category-badge {
            font-size: 0.7rem; font-weight: 600;
            padding: 0.2rem 0.6rem; border-radius: 20px;
            color: white; display: inline-block; margin-bottom: 0.25rem;
        }
        .category-pessoal { background-color: #6f42c1; }
        .category-profissional { background-color: #0d6efd; }
        .category-financeiro { background-color: #198754; }
        .category-educação { background-color: #fd7e14; }
        .category-saúde { background-color: #dc3545; }

        .ranking-list .rank-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.75rem 0;
        }
        .ranking-list .rank { font-weight: 600; color: var(--grey); width: 20px; text-align: center; }
        .ranking-list .rank-1 { color: var(--gold); }
        .ranking-list .rank-2 { color: var(--silver); }
        .ranking-list .rank-3 { color: var(--bronze); }
        .ranking-list img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .ranking-list .xp { margin-left: auto; font-weight: 500; }

        .view-all-link { display: block; text-align: center; margin-top: 1rem; font-weight: 500; color: var(--primary); text-decoration: none; }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 5px solid var(--danger);
            padding: 1.5rem;
            border-radius: 8px;
            color: var(--dark);
        }

        .gamification-btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .gamification-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .gamification-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .btn-conquistas {
            background-color: rgba(29, 132, 181, 0.1);
            color: var(--primary);
        }
        .btn-conquistas:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .btn-recompensas {
            background-color: rgba(75, 198, 177, 0.1);
            color: var(--secondary);
        }
        .btn-recompensas:hover {
            background-color: var(--secondary);
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 1200px) {
            .main-column, .side-column {
                grid-column: span 12;
            }
        }
        
        @media (max-width: 768px) {
            body { margin-left: var(--sidebar-width-collapsed); }
            .sidebar { width: var(--sidebar-width-collapsed); align-items: center; }
            .logo .text, .nav-text { display: none; }
            .nav-icon { margin-right: 0; }
        }

    </style>
</head>
<body class="sidebar-collapsed">

    <aside class="sidebar">
        <div class="logo">M<span><span class="text">XP</span></span></div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="active" title="Dashboard">
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
                <a href="nova_meta.php" class="btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Nova Meta
                </a>
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
            <?php if ($db_error): ?>
                <div class="alert-danger">
                    <h4>Ocorreu um erro na Base de Dados</h4>
                    <p>Não foi possível carregar os dados do seu dashboard. Por favor, siga as instruções no topo do ficheiro <code>dashboard.php</code>.</p>
                    <?php if (DEBUG_MODE): ?>
                        <hr>
                        <p><small>Detalhe técnico: <?= $db_error ?></small></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <header class="header">
                    <h1>Olá, <?= $nomeUsuario ?>!</h1>
                    <p>Pronto para conquistar os seus objetivos hoje?</p>
                </header>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon level"><i class="bi bi-star-fill"></i></div>
                        <div>
                            <div class="value">Nível <?= $nivel ?></div>
                            <div class="label">Seu Nível Atual</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon xp"><i class="bi bi-lightning-fill"></i></div>
                        <div>
                            <div class="value"><?= $xp ?></div>
                            <div class="label">Pontos de Experiência</div>
                        </div>
                    </div>
                    <div class="stat-card">
                         <div class="icon goals"><i class="bi bi-check-circle-fill"></i></div>
                         <div>
                            <div class="value"><?= $metasConcluidas ?></div>
                            <div class="label">Metas Concluídas</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Coluna Principal (Mais Larga) -->
                    <div class="main-column">
                        <div class="card">
                            <div class="card-header">
                                <span class="icon"><i class="bi bi-list-check"></i></span>
                                Progresso Atual
                            </div>
                            <div id="lista-metas">
                                <?php if (empty($metas)): ?>
                                    <p class="text-muted">Nenhuma meta cadastrada ainda. Que tal criar uma?</p>
                                <?php else: ?>
                                    <?php foreach ($metas as $meta): ?>
                                        <div class="meta-progress-item">
                                            <div class="meta-info">
                                                <span class="meta-category-badge category-<?= strtolower(htmlspecialchars($meta['categoria'])) ?>">
                                                    <?= htmlspecialchars($meta['categoria']) ?>
                                                </span>
                                                <strong><?= htmlspecialchars($meta['titulo']) ?></strong>
                                            </div>
                                            <div class="meta-due-date">
                                                <i class="bi bi-calendar-event"></i>
                                                <span><?= date('d/m/Y', strtotime($meta['prazo'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                         <div class="card">
                             <div class="card-header">
                                <span class="icon"><i class="bi bi-graph-up"></i></span>
                                Progresso Semanal
                            </div>
                            <div style="height: 250px;">
                                <canvas id="graficoProgresso"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coluna Lateral (Mais Estreita) -->
                    <div class="side-column">
                        <div class="card">
                            <div class="card-header">
                                <span class="icon"><i class="bi bi-joystick"></i></span>
                                Gamificação
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>Progresso de Nível</strong>
                                    <small class="text-muted"><?= $xp ?> / <?= $xpParaProximoNivel ?> XP</small>
                                </div>
                                <div class="xp-progress">
                                    <div class="xp-progress-bar" style="width: <?= $progressoXP ?>%;"></div>
                                </div>
                                <div class="gamification-btn-group">
                                    <a href="conquistas.php" class="gamification-btn btn-conquistas">
                                        <i class="bi bi-trophy-fill"></i>
                                        <span>Conquistas</span>
                                    </a>
                                    <a href="recompensas.php" class="gamification-btn btn-recompensas">
                                        <i class="bi bi-gift-fill"></i>
                                        <span>Recompensas</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <span class="icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                                Top 5 Utilizadores
                            </div>
                            <div class="ranking-list">
                                <?php foreach ($ranking_users as $index => $ranked_user): ?>
                                    <?php 
                                        $rank = $index + 1;
                                        $ranked_user_avatar = ($ranked_user['foto_perfil'] && file_exists($ranked_user['foto_perfil'])) ? htmlspecialchars($ranked_user['foto_perfil']) : "https://i.pravatar.cc/150?u=" . urlencode($ranked_user['email']);
                                    ?>
                                    <div class="rank-item">
                                        <span class="rank rank-<?= $rank <= 3 ? $rank : '' ?>"><?= $rank ?></span>
                                        <img src="<?= $ranked_user_avatar ?>" alt="Avatar">
                                        <span><?= htmlspecialchars($ranked_user['username']) ?></span>
                                        <span class="xp"><?= number_format($ranked_user['xp']) ?> XP</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="ranking.php" class="view-all-link">Ver Ranking Completo</a>
                        </div>

                         <div class="card">
                             <div class="card-header">
                                <span class="icon"><i class="bi bi-robot"></i></span>
                                Dica da Meta.IA
                            </div>
                            <p id="dica-ia" class="text-muted">🤖 Que tal dividir a sua meta 'Aprender a Cozinhar' na tarefa 'Fazer um curso de culinária online'?</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Lógica da Sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Só executa o script do gráfico se o elemento existir (para não dar erro na página de erro)
        if (document.getElementById("graficoProgresso")) {
            const ctx = document.getElementById("graficoProgresso").getContext("2d");
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(75, 198, 177, 0.6)');
            gradient.addColorStop(1, 'rgba(75, 198, 177, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"],
                    datasets: [{
                        label: "XP ganho por dia",
                        data: [30, 45, 50, 60, 40, 70, 25],
                        borderColor: "var(--secondary)",
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'var(--secondary)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'var(--secondary)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    </script>
</body>
</html>

