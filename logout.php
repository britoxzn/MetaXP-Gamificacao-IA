<?php
// --- LÓGICA DE LOGOUT ---
// Garante que a sessão seja iniciada para poder ser destruída.
session_start();

// Remove todas as variáveis de sessão.
session_unset();

// Destrói a sessão.
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saindo... - MetaXP</title>
    <!-- Redireciona para a página de login após 5 segundos -->
    <meta http-equiv="refresh" content="5;url=login.php">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1D84B5;
            --secondary: #4BC6B1;
            --light: #F8F9FA;
            --grey: #6c757d;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite linear;
            z-index: -1;
        }
        body::before {
            width: 30vw; height: 30vw; bottom: -15vw; left: -15vw;
        }
        body::after {
            width: 40vw; height: 40vw; top: -20vw; right: -20vw; animation-duration: 25s; animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-120vh) rotate(360deg); }
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        .logo span {
            color: var(--secondary);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 1rem;
            color: var(--grey);
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .login-btn {
            display: inline-block;
            width: 100%; 
            padding: 1rem; 
            background: linear-gradient(90deg, var(--secondary), var(--primary)); 
            background-size: 200%; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.4s;
            text-decoration: none;
        }
        .login-btn:hover {
            background-position: right; 
            transform: translateY(-3px); 
            box-shadow: 0 4px 15px rgba(29, 132, 181, 0.4);
        }
        
        @media (max-width: 480px) {
            .logout-container {
                padding: 2rem 1.5rem; margin: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="logout-container">
        <div class="logo">Meta<span>XP</span></div>
        <h2 class="title">Você saiu com sucesso!</h2>
        <p class="subtitle">A sua sessão foi encerrada com segurança. Esperamos vê-lo em breve.</p>
        <p class="subtitle" style="font-size: 0.9rem;">Você será redirecionado em 5 segundos...</p>
        <a href="login.php" class="login-btn">Voltar para o Login</a>
    </div>

</body>
</html>
