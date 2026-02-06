<?php
// Configurações do banco de dados - MetaXP
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_NAME', 'if0_38657243_plataforma_de_planejamento_metas_pessoais');
define('DB_USER', 'if0_38657243');
define('DB_PASS', 'metaspessoais');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Falha na conexão com o banco de dados: " . $e->getMessage());
}
?>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MetaXP - Plataforma de Metas e Produtividade</title>
    
    <!-- Fontes e Estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fb;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .app-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content .logo {
            font-size: 24px;
            font-weight: 600;
        }

        .header-content .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .app-main {
            display: flex;
            flex: 1;
            background-color: #ffffff;
        }

        .sidebar {
            width: 250px;
            background-color: #007bff;
            color: white;
            padding-top: 30px;
        }

        .app-nav ul {
            list-style-type: none;
            padding: 0;
        }

        .app-nav ul li {
            padding: 10px 20px;
            text-align: left;
        }

        .app-nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 8px;
            transition: background-color 0.3s;
        }

        .app-nav ul li a:hover {
            background-color: #0056b3;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .xp-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .xp-display {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }

        .level-display {
            font-size: 18px;
            color: #333;
        }

        .xp-progress {
            background-color: #e6f0ff;
            border-radius: 25px;
            height: 8px;
            width: 100%;
            margin-top: 10px;
        }

        .xp-progress-bar {
            background-color: #007bff;
            height: 100%;
            border-radius: 25px;
        }

        .goals-section {
            margin-top: 40px;
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .goal-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .goal-header h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .goal-category {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .goal-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }

        .goal-due {
            font-weight: bold;
        }

        .goal-xp {
            color: #007bff;
            font-weight: bold;
        }

        /* Adicionando estilo para a IA Coach */
        .ai-section {
            margin-top: 40px;
        }

        .ai-section h2 {
            font-size: 24px;
            color: #007bff;
        }
    </style>
</head>
<body class="app-theme">

<div class="app-container">
    <header class="app-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-bullseye"></i>
                <span>MetaXP</span>
            </div>
            <div class="user-profile">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <img src="assets/avatars/<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="Avatar do Usuário">
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="app-main">
        <aside class="sidebar">
            <nav class="app-nav">
                <ul>
                    <li class="active"><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-bullseye"></i> Metas</a></li>
                    <li><a href="#"><i class="fas fa-tasks"></i> Tarefas</a></li>
                    <li><a href="#"><i class="fas fa-trophy"></i> Recompensas</a></li>
                    <li><a href="#"><i class="fas fa-robot"></i> IA Coach</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <section class="welcome-section">
                <h1>Olá, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                <p>Seu progresso hoje com base nas metas e tarefas concluídas:</p>

                <div class="xp-container">
                    <div class="xp-display">
                        <span class="xp-value"><?= $_SESSION['xp_points'] ?? 0 ?></span>
                        <span class="xp-label">XP</span>
                    </div>
                    <div class="level-display">
                        Nível <?= $_SESSION['level'] ?? 1 ?>
                    </div>
                </div>

                <div class="xp-progress">
                    <div class="xp-progress-bar" style="width: <?= ($_SESSION['xp_points'] % 1000) / 10 ?>%"></div>
                </div>
            </section>

            <section class="goals-section">
                <h2><i class="fas fa-bullseye"></i> Suas Metas Ativas</h2>

                <div class="goals-grid">
                    <?php
                    require_once 'includes/config.php';
                    $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND status != 'completed' ORDER BY due_date ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                    while($goal = $stmt->fetch()):
                    ?>
                    <div class="goal-card">
                        <div class="goal-header">
                            <h3><?= htmlspecialchars($goal['title']) ?></h3>
                            <span class="goal-category category-<?= strtolower($goal['category']) ?>">
                                <?= ucfirst($goal['category']) ?>
                            </span>
                        </div>
                        <p><?= htmlspecialchars($goal['description']) ?></p>

                        <div class="goal-footer">
                            <span class="goal-due">Prazo: <?= date('d/m/Y', strtotime($goal['due_date'])) ?></span>
                            <span class="goal-xp">+<?= $goal['xp_reward'] ?> XP</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <section class="ai-section">
                <h2><i class="fas fa-robot"></i> IA Coach</h2>
                <!-- Adicionar funcionalidade do Coach de IA aqui -->
            </section>
        </div>
    </main>
</div>

</body>
</html>
