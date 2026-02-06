<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$nomeUsuario = $_SESSION['username'] ?? 'Usuário';
$emailUsuario = $_SESSION['email'] ?? 'email@exemplo.com';
$fotoPerfil = "https://i.pravatar.cc/150?u=" . urlencode($emailUsuario);
?>

<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>MetaXP | Recompensas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- CDN Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f3f4f8;
            color: #222;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            background: #2d3250;
            color: #fff;
            height: 100vh;
            padding: 30px 20px;
            position: fixed;
            width: 250px;
            top: 0;
            left: 0;
        }

        .sidebar img {
            width: 70px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .sidebar h5 {
            margin-bottom: 0;
        }

        .sidebar .nav-link {
            color: #ccc;
            margin-top: 15px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #1cc88a;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 8px;
        }

        .main {
            margin-left: 270px;
            padding: 40px 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .btn-custom {
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            color: white;
            border-radius: 30px;
            font-weight: 500;
        }

        .theme-toggle {
            position: absolute;
            right: 20px;
            top: 20px;
            cursor: pointer;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column align-items-center text-center">
        <img src="<?= $fotoPerfil ?>" alt="Foto">
        <h5><?= htmlspecialchars($nomeUsuario) ?></h5>
        <small><?= htmlspecialchars($emailUsuario) ?></small>
        <hr class="w-100 my-3">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Painel</a>
        <a href="conquistas.php" class="nav-link"><i class="bi bi-trophy"></i> Conquistas</a>
        <a href="editar_perfil.php" class="nav-link"><i class="bi bi-person"></i> Perfil</a>
        <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Sair</a>
    </div>

    <!-- Conteúdo -->
    <div class="main">
        <div class="theme-toggle" onclick="toggleTheme()">
            <i class="bi bi-moon-fill"></i>
        </div>

        <h2 class="mb-4">🎁 Recompensas Disponíveis</h2>

        <div class="row">
            <!-- Exemplo de recompensa -->
            <?php
            // Simulando recompensas (ideal: puxar do banco de dados)
            $recompensas = [
                ["titulo" => "Café Grátis", "descricao" => "Um café de sua escolha", "xp" => 100],
                ["titulo" => "Dia de Folga", "descricao" => "Um dia livre para relaxar", "xp" => 250],
                ["titulo" => "Gift Card", "descricao" => "Vale-presente de R$ 50", "xp" => 500],
            ];

            foreach ($recompensas as $rec) {
                echo '
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title">' . htmlspecialchars($rec["titulo"]) . '</h5>
                                <p class="card-text text-muted">' . htmlspecialchars($rec["descricao"]) . '</p>
                            </div>
                            <button class="btn btn-custom mt-3">Resgatar por ' . $rec["xp"] . ' XP</button>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <div class="alert alert-info mt-4">
            🤖 <strong>Dica:</strong> Continue completando metas para acumular mais XP e desbloquear recompensas exclusivas!
        </div>
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.querySelector('.theme-toggle i');
            if (html.getAttribute('data-bs-theme') === 'light') {
                html.setAttribute('data-bs-theme', 'dark');
                icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
            } else {
                html.setAttribute('data-bs-theme', 'light');
                icon.classList.replace('bi-sun-fill', 'bi-moon-fill');
            }
        }
    </script>
</body>
</html>
