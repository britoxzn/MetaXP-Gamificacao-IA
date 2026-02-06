<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Inclui a função de verificação de conquistas
include_once 'helpers/gamification_helper.php';

// Configurações do DB
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_NAME', 'if0_38657243_plataforma_de_planejamento_metas_pessoais');
define('DB_USER', 'if0_38657243');
define('DB_PASS', 'metaspessoais');
define('DB_CHARSET', 'utf8mb4');

$pdo = null;
try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$meta_id = $_GET['id'] ?? null;

if (!$meta_id) {
    header("Location: dashboard.php");
    exit();
}

// Busca os detalhes da meta para garantir que pertence ao utilizador logado
try {
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND user_id = ?");
    $stmt->execute([$meta_id, $user_id]);
    $meta = $stmt->fetch();

    if (!$meta) {
        // Se a meta não for encontrada ou não pertencer ao utilizador, redireciona
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar detalhes da meta: " . $e->getMessage());
}


// Obtém os dados do utilizador da sessão para o layout
$nomeUsuario = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$fotoAtual = $_SESSION['foto_perfil'] ?? null;
$fotoPerfil = ($fotoAtual && file_exists($fotoAtual)) ? htmlspecialchars($fotoAtual) : "https://i.pravatar.cc/150?u=" . urlencode($_SESSION['email'] ?? 'default');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Meta - MetaXP</title>
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
        .user-menu img { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; object-fit: cover; }
        .main-content { padding: 2rem; flex-grow: 1; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }
        .header p { color: var(--grey); }
        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        .tarefa-list { list-style: none; padding: 0; }
        .tarefa-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        .tarefa-item:last-child { border-bottom: none; }
        .tarefa-item.completed { text-decoration: line-through; color: var(--grey); }
        .tarefa-checkbox {
            width: 20px; height: 20px;
            margin-right: 1rem;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .tarefa-descricao { flex-grow: 1; }
        .tarefa-actions .btn-delete {
            background: none; border: none; color: var(--grey);
            cursor: pointer; transition: color 0.3s;
        }
        .tarefa-actions .btn-delete:hover { color: var(--danger); }
        .add-tarefa-form { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .form-control {
            width: 100%; padding: 0.8rem 1rem; border: 1px solid #ddd;
            border-radius: 8px; font-size: 1rem; font-family: 'Poppins', sans-serif;
            transition: all 0.3s; flex-grow: 1;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(29, 132, 181, 0.2); }
        .btn-primary {
            background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%;
            color: white; border: none; border-radius: 8px; font-weight: 500;
            padding: 0.7rem 1.5rem; transition: all 0.4s ease; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;
        }
        .btn-primary:hover { background-position: right; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.3); }
        .toast {
            position: fixed; bottom: 20px; right: 20px;
            background-color: var(--dark); color: white;
            padding: 1rem 1.5rem; border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(200%);
            transition: transform 0.5s ease-in-out;
            z-index: 1050;
        }
        .toast.show { transform: translateY(0); }
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
                    <img src="<?= $fotoPerfil ?>" alt="Foto do Perfil" title="<?= $nomeUsuario ?>">
                </div>
            </div>
        </nav>
        
        <main class="main-content">
            <header class="header">
                <div>
                    <h1><?= htmlspecialchars($meta['titulo']) ?></h1>
                    <p><?= htmlspecialchars($meta['descricao']) ?></p>
                </div>
                <a href="ver_mais_metas.php" class="btn-primary" style="background: var(--grey);">Voltar</a>
            </header>
            
            <div class="card">
                <ul class="tarefa-list" id="tarefa-list-container">
                    <!-- As tarefas serão carregadas aqui pelo JavaScript -->
                </ul>
                <form class="add-tarefa-form" id="add-tarefa-form">
                    <input type="text" id="nova-tarefa-input" class="form-control" placeholder="Adicionar nova tarefa..." required>
                    <button type="submit" class="btn-primary">Adicionar</button>
                </form>
            </div>
        </main>
    </div>
    
    <div id="toast-notification" class="toast"></div>
    <audio id="complete-sound" src="https://www.myinstants.com/media/sounds/y2mate_mp3-roblox-death-sound-effect.mp3" preload="auto"></audio>

    <script>
        document.getElementById('toggle-sidebar').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        const metaId = <?= json_encode($meta_id) ?>;
        const tarefaListContainer = document.getElementById('tarefa-list-container');
        const addTarefaForm = document.getElementById('add-tarefa-form');
        const novaTarefaInput = document.getElementById('nova-tarefa-input');
        const toast = document.getElementById('toast-notification');
        const completeSound = document.getElementById('complete-sound');

        // Função para mostrar notificação (toast)
        function showToast(message) {
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Função para renderizar a lista de tarefas
        function renderTarefas(tarefas) {
            tarefaListContainer.innerHTML = '';
            if (tarefas.length === 0) {
                tarefaListContainer.innerHTML = '<p class="text-center text-muted p-3">Nenhuma tarefa adicionada ainda. Comece a planear os seus próximos passos!</p>';
                return;
            }
            tarefas.forEach(tarefa => {
                const li = document.createElement('li');
                li.className = `tarefa-item ${tarefa.concluida ? 'completed' : ''}`;
                li.dataset.tarefaId = tarefa.id;
                
                li.innerHTML = `
                    <input type="checkbox" class="tarefa-checkbox" ${tarefa.concluida ? 'checked' : ''}>
                    <span class="tarefa-descricao">${tarefa.descricao}</span>
                    <div class="tarefa-actions">
                        <button class="btn-delete"><i class="bi bi-trash-fill"></i></button>
                    </div>
                `;
                tarefaListContainer.appendChild(li);
            });
        }

        // Função para carregar tarefas
        async function carregarTarefas() {
            try {
                const response = await fetch(`api/tarefas_handler.php?meta_id=${metaId}`);
                const data = await response.json();
                if (data.status === 'success') {
                    renderTarefas(data.data);
                } else {
                    showToast('Erro ao carregar tarefas.');
                }
            } catch (error) {
                showToast('Erro de rede ao carregar tarefas.');
            }
        }

        // Função para adicionar uma nova tarefa
        addTarefaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const descricao = novaTarefaInput.value.trim();
            if (!descricao) return;

            try {
                const response = await fetch('api/tarefas_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meta_id: metaId, descricao: descricao })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    novaTarefaInput.value = '';
                    carregarTarefas();
                } else {
                    showToast(data.message || 'Erro ao adicionar tarefa.');
                }
            } catch (error) {
                showToast('Erro de rede ao adicionar tarefa.');
            }
        });

        // Event listener para ações na lista de tarefas (concluir/apagar)
        tarefaListContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const tarefaItem = target.closest('.tarefa-item');
            if (!tarefaItem) return;

            const tarefaId = tarefaItem.dataset.tarefaId;

            // Marcar/Desmarcar tarefa como concluída
            if (target.classList.contains('tarefa-checkbox')) {
                const isConcluida = target.checked;
                try {
                    const response = await fetch('api/tarefas_handler.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tarefa_id: tarefaId, concluida: isConcluida })
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        tarefaItem.classList.toggle('completed', isConcluida);
                        if(isConcluida) {
                            completeSound.play();
                            showToast(`+${data.xp_ganho} XP! Conquista: ${data.conquista || 'Nenhuma'}`);
                        }
                    } else {
                        showToast(data.message || 'Erro ao atualizar tarefa.');
                        target.checked = !isConcluida; // Reverte a checkbox em caso de erro
                    }
                } catch (error) {
                    showToast('Erro de rede ao atualizar tarefa.');
                    target.checked = !isConcluida;
                }
            }

            // Apagar tarefa
            if (target.closest('.btn-delete')) {
                if (confirm('Tem a certeza que deseja apagar esta tarefa?')) {
                    try {
                         const response = await fetch('api/tarefas_handler.php', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ tarefa_id: tarefaId })
                        });
                        const data = await response.json();
                        if (data.status === 'success') {
                            tarefaItem.remove();
                        } else {
                            showToast(data.message || 'Erro ao apagar tarefa.');
                        }
                    } catch (error) {
                         showToast('Erro de rede ao apagar tarefa.');
                    }
                }
            }
        });

        // Carregar tarefas iniciais
        document.addEventListener('DOMContentLoaded', carregarTarefas);
    </script>
</body>
</html>