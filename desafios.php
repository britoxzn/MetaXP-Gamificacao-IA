<?php
// Verifica se o usuário está logado
session_start();

// Se não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Simulação de obtenção de dados de desafios do banco de dados
$user_id = $_SESSION['user_id']; // ID do usuário logado
// Você deve substituir isso com uma consulta ao banco de dados para obter os desafios do usuário
$desafios = [
    "Desafio 1: Completar 5km correndo",
    "Desafio 2: Ler 3 livros",
    "Desafio 3: Economizar R$500,00"
];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desafios - MetaXP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 1000px;
            margin-top: 50px;
        }

        .card {
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #3e64ff;
            color: white;
            font-size: 20px;
            font-weight: bold;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .card-body {
            background-color: white;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 25px;
        }

        .text-center a {
            margin: 10px 15px;
        }

    </style>
</head>

<body>
    <div class="container">
        <h2>Desafios Completos</h2>
        <div class="card">
            <div class="card-header">
                Seus Desafios
            </div>
            <div class="card-body">
                <ul>
                    <?php foreach ($desafios as $desafio): ?>
                        <li><?php echo $desafio; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="text-center mt-4">
           <a href="perfil.php" class="btn btn-primary">Voltar ao Perfil</a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
