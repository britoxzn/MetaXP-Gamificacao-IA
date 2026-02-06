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
$meta_id = $_GET['id'] ?? null;
$errors = [];

if (!$meta_id) {
    header("Location: ver_mais_metas.php");
    exit();
}

// --- Processamento do Formulário (Update e Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AÇÃO DE EXCLUIR ---
    if ($action === 'delete') {
        try {
            $stmt_delete_tarefas = $pdo->prepare("DELETE FROM tarefas WHERE meta_id = ?");
            $stmt_delete_tarefas->execute([$meta_id]);

            $sql = "DELETE FROM metas WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$meta_id, $user_id]);
            
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Meta excluída com sucesso!'];
            header("Location: ver_mais_metas.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Erro ao excluir a meta: " . $e->getMessage();
        }
    }
    
    // --- AÇÃO DE ATUALIZAR ---
    elseif ($action === 'update') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $prazo = $_POST['prazo'] ?? '';
        $categoria = $_POST['categoria'] ?? 'Pessoal';
        $status = $_POST['status'] ?? 'pendente';

        if (empty($titulo)) { $errors[] = 'O título da meta é obrigatório.'; }
        if (empty($prazo)) { $errors[] = 'A data limite é obrigatória.'; }

        if (empty($errors)) {
            try {
                $sql = "UPDATE metas SET titulo = ?, descricao = ?, status = ?, prazo = ?, categoria = ? WHERE id = ? AND user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $descricao, $status, $prazo, $categoria, $meta_id, $user_id]);
                
                // Prepara a mensagem de sucesso e redireciona
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Meta atualizada com sucesso!'];
                header("Location: ver_mais_metas.php");
                exit();

            } catch (PDOException $e) {
                $errors[] = "Erro ao atualizar a meta: " . $e->getMessage();
            }
        }
    }
}

// Busca os detalhes da meta para preencher o formulário
try {
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND user_id = ?");
    $stmt->execute([$meta_id, $user_id]);
    $meta = $stmt->fetch();
    if (!$meta) {
        header("Location: ver_mais_metas.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar detalhes da meta: " . $e->getMessage());
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
    <title>Editar Meta - MetaXP</title>
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

        .btn {
            border: none; border-radius: 8px; font-weight: 500;
            padding: 0.7rem 1.5rem;
            transition: all 0.4s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%;
            color: white; 
        }
        .btn-primary:hover { background-position: right; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3); }
        
        .btn-secondary { background-color: #e9ecef; color: var(--grey); }
        .btn-secondary:hover { background-color: #d8dde3; }

        .btn-danger { background-color: var(--danger); color: white; }
        .btn-danger:hover { background-color: #c82333; }

        .form-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .form-group { margin-bottom: 1.5rem; }
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
        
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;
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
                <h1>Editar Meta</h1>
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

                <form action="editar_meta.php?id=<?= $meta_id ?>" method="POST">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label for="titulo" class="form-label">Título da Meta</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" value="<?= htmlspecialchars($meta['titulo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao" class="form-label">Descrição (Opcional)</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="4"><?= htmlspecialchars($meta['descricao']) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria" class="form-label">Categoria</label>
                                <select id="categoria" name="categoria" class="form-control">
                                    <option value="Pessoal" <?= $meta['categoria'] === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
                                    <option value="Profissional" <?= $meta['categoria'] === 'Profissional' ? 'selected' : '' ?>>Profissional</option>
                                    <option value="Financeiro" <?= $meta['categoria'] === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                                    <option value="Educação" <?= $meta['categoria'] === 'Educação' ? 'selected' : '' ?>>Educação</option>
                                    <option value="Saúde" <?= $meta['categoria'] === 'Saúde' ? 'selected' : '' ?>>Saúde</option>
                                    <option value="Outro" <?= $categoria === 'Outro' ? 'selected' : '' ?>>Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="form-group">
                                <label for="prazo" class="form-label">Data Limite</label>
                                <input type="date" id="prazo" name="prazo" class="form-control" value="<?= htmlspecialchars($meta['prazo']) ?>" required>
                            </div>
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="pendente" <?= $meta['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="em_andamento" <?= $meta['status'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="concluida" <?= $meta['status'] === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-danger" id="delete-button">Excluir Meta</button>
                        <div class="d-flex gap-2">
                            <a href="ver_mais_metas.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </div>
                </form>
                
                <!-- Formulário separado apenas para a ação de excluir -->
                <form id="delete-form" action="editar_meta.php?id=<?= $meta_id ?>" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="delete">
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Lógica para confirmar exclusão
        document.getElementById('delete-button').addEventListener('click', function() {
            if (confirm('Tem a certeza que deseja excluir esta meta? Esta ação não pode ser desfeita.')) {
                document.getElementById('delete-form').submit();
            }
        });
    </script>
</body>
</html>

