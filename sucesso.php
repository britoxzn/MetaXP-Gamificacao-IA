<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MetaXP | Sucesso</title>

    <!-- Fontes e estilos -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-gradient-metaxp {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
        }

        .logo-inteligencia {
            font-size: 2rem;
            color: #4e73df;
            font-weight: bold;
        }

        .text-brand {
            color: #1cc88a;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #375ac2;
            border-color: #375ac2;
        }
    </style>
</head>

<body class="bg-gradient-metaxp">

    <div class="container">
        <!-- Linha externa -->
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Linha interna do card -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-password-image" style="background-image: url('img/sucesso.png'); background-size: cover;"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <div class="logo-inteligencia">Meta<span class="text-brand">XP</span></div>
                                        <h1 class="h5 text-gray-900 mt-3"> Sucesso!</h1>
                                        <?php if (isset($_SESSION['sucesso'])): ?>
                                            <p class="text-muted"><?php echo $_SESSION['sucesso']; ?></p>
                                            <?php unset($_SESSION['sucesso']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="login.php"> Voltar para o login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
