<?php
// Verifica se o usuário está logado
session_start();

// Se não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verifica se o nome e foto do usuário estão preenchidos
if (empty($_SESSION['user_name']) || empty($_SESSION['user_photo'])) {
    // Redireciona para o perfil.html caso os dados não estejam preenchidos
    header('Location: perfil.html');
    exit();
}

// Caso contrário, carrega os dados do perfil
$user_name = $_SESSION['user_name'];
$user_photo = $_SESSION['user_photo'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - MetaXP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados */
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <!-- Exibe a foto do perfil -->
            <img src="<?php echo $user_photo; ?>" alt="Imagem de perfil" class="profile-image">
            <h2>Bem-vindo, <?php echo htmlspecialchars($user_name); ?></h2>
            <p>Seu perfil no MetaXP - A plataforma para planejar suas metas e acompanhar seu progresso.</p>
        </div>
        <!-- Conteúdo do perfil -->
    </div>
</body>
</html>
