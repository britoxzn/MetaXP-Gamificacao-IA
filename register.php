<?php

// --- BLOCO DE DEPURAÇÃO ---

// Estas linhas forçam o PHP a mostrar os erros no ecrã em vez do erro 500.

// Isto ajuda-nos a descobrir o problema exato.

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

// --- FIM DO BLOCO DE DEPURAÇÃO ---



session_start();



// Configurações do ambiente

define('SITE_NAME', 'MetaXP');

define('SITE_URL', 'https://plataforma.metaxp.com');

define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');



// Configurações do banco de dados

define('DB_HOST', 'sql212.infinityfree.com');

define('DB_NAME', 'if0_38657243_plataforma_de_planejamento_metas_pessoais');

define('DB_USER', 'if0_38657243');

define('DB_PASS', 'metaspessoais');

define('DB_CHARSET', 'utf8mb4');



date_default_timezone_set(DEFAULT_TIMEZONE);



// Conexão com o banco de dados

$pdo = null;

try {

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;

$options = [

PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

PDO::ATTR_EMULATE_PREPARES => false,

];

$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {

error_log("Erro de conexão com o banco de dados: " . $e->getMessage());

die("Desculpe, estamos com problemas técnicos. Por favor, tente novamente mais tarde.");

}



// Redireciona para o dashboard se o usuário já estiver logado

if (isset($_SESSION['user_id'])) {

header("Location: dashboard.php");

exit();

}



$errors = [];

$username = '';

$email = '';



// --- INÍCIO DA LÓGICA DE CADASTRO ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$username = trim($_POST['username'] ?? '');

$email = trim($_POST['email'] ?? '');

$password = $_POST['password'] ?? '';

$confirm_password = $_POST['confirm_password'] ?? '';



// 1. Validação dos dados

if (empty($username)) {

$errors['username'] = 'O nome de usuário é obrigatório.';

}

if (empty($email)) {

$errors['email'] = 'O e-mail é obrigatório.';

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

$errors['email'] = 'Por favor, insira um e-mail válido.';

}

if (empty($password)) {

$errors['password'] = 'A senha é obrigatória.';

} elseif (strlen($password) < 8) {

$errors['password'] = 'A senha deve ter no mínimo 8 caracteres.';

}

if ($password !== $confirm_password) {

$errors['confirm_password'] = 'As senhas não coincidem.';

}



// 2. Verifica se o e-mail já existe

if (empty($errors)) {

// CORREÇÃO: Alterado de 'usuarios' para 'users'

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");

$stmt->execute([$email]);

if ($stmt->fetch()) {

$errors['email'] = 'Este e-mail já está em uso.';

}

}



// 3. Se não houver erros, insere no banco

if (empty($errors)) {

try {

$hashed_password = password_hash($password, PASSWORD_BCRYPT);



// CORREÇÃO: Alterado de 'usuarios' para 'users' e a coluna 'nome' para 'username' (assumindo que seja este o nome na sua tabela 'users')

$sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([$username, $email, $hashed_password]);



// --- AUTO-LOGIN E REDIRECIONAMENTO ---

$new_user_id = $pdo->lastInsertId();


session_regenerate_id(true);

$_SESSION['user_id'] = $new_user_id;

$_SESSION['username'] = $username;


header("Location: dashboard.php");

exit();



} catch (PDOException $e) {

error_log("Erro no cadastro: " . $e->getMessage());

// Com o debug ativado, a die() abaixo mostrará o erro real.

die("Erro de Banco de Dados: " . $e->getMessage());

}

}

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Cadastro - MetaXP</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

:root {

--primary: #1D84B5;

--secondary: #4BC6B1;

--accent: #FF6584;

--light: #F8F9FA;

--dark: #212529;

--danger: #DC3545;

--success: #1cc88a;

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

padding: 2rem 0;

overflow-x: hidden;

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



.register-container {

background: rgba(255, 255, 255, 0.85);

backdrop-filter: blur(10px);

padding: 2.5rem;

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



.subtitle {

font-size: 1rem;

color: var(--grey);

margin-bottom: 2rem;

font-weight: 400;

}



.form-group {

position: relative;

margin-bottom: 1.8rem;

text-align: left;

}



.form-input {

width: 100%;

padding: 1rem 1rem 1rem 3.5rem;

border: 1px solid #ccc;

border-radius: 8px;

font-size: 1rem;

font-family: 'Poppins', sans-serif;

background: var(--light);

transition: all 0.3s;

}

.form-input.is-invalid {

border-color: var(--danger);

}

.form-input:focus {

outline: none;

border-color: var(--primary);

box-shadow: 0 0 0 4px rgba(29, 132, 181, 0.2);

}

.form-input:focus.is-invalid {

box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.2);

}



.form-label {

position: absolute;

top: 50%;

left: 3.5rem;

transform: translateY(-50%);

color: var(--grey);

pointer-events: none;

transition: all 0.3s;

}


.form-input:focus ~ .form-label,

.form-input:not(:placeholder-shown) ~ .form-label {

top: -10px; left: 10px; font-size: 0.8rem; background: white; padding: 0 5px; color: var(--primary);

}

.form-input.is-invalid:focus ~ .form-label {

color: var(--danger);

}


.form-group .icon {

position: absolute;

top: 50%;

left: 1rem;

transform: translateY(-50%);

color: var(--grey);

transition: color 0.3s;

}

.form-input:focus ~ .icon {

color: var(--primary);

}

.form-input.is-invalid:focus ~ .icon {

color: var(--danger);

}


.error-message {

color: var(--danger);

font-size: 0.8rem;

padding-left: 0.5rem;

margin-top: 0.25rem;

}



.submit-btn {

width: 100%; padding: 1rem; background: linear-gradient(90deg, var(--secondary), var(--primary)); background-size: 200%; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.4s;

}

.submit-btn:hover {

background-position: right; transform: translateY(-3px); box-shadow: 0 4px 15px rgba(29, 132, 181, 0.4);

}



.login-link {

margin-top: 1.5rem;

font-size: 0.9rem;

}

.login-link a {

color: var(--primary);

text-decoration: none;

font-weight: 600;

transition: all 0.2s;

}

.login-link a:hover {

text-decoration: underline;

}


.alert {

padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; color: var(--dark); text-align: left;

background-color: rgba(220, 53, 69, 0.1); border-left: 5px solid var(--danger);

}



@media (max-width: 480px) {

.register-container {

padding: 2rem 1.5rem; margin: 1rem;

}

}

</style>

</head>

<body>

<div class="register-container">

<div class="logo">Meta<span>XP</span></div>

<p class="subtitle">Crie sua conta e comece sua jornada</p>



<?php if (!empty($errors['general'])): ?>

<div class="alert"><?= htmlspecialchars($errors['general']) ?></div>

<?php endif; ?>



<form action="register.php" method="POST" novalidate>

<div class="form-group">

<span class="icon">

<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z"/></svg>

</span>

<input type="text" id="username" name="username" class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>" required placeholder=" " value="<?= htmlspecialchars($username) ?>">

<label for="username" class="form-label">Nome de Usuário</label>

<?php if (isset($errors['username'])): ?><div class="error-message"><?= $errors['username'] ?></div><?php endif; ?>

</div>



<div class="form-group">

<span class="icon">

<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>

</span>

<input type="email" id="email" name="email" class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" required placeholder=" " value="<?= htmlspecialchars($email) ?>">

<label for="email" class="form-label">Seu e-mail</label>

<?php if (isset($errors['email'])): ?><div class="error-message"><?= $errors['email'] ?></div><?php endif; ?>

</div>


<div class="form-group">

<span class="icon">

<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>

</span>

<input type="password" id="password" name="password" class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8" placeholder=" ">

<label for="password" class="form-label">Crie uma senha</label>

<?php if (isset($errors['password'])): ?><div class="error-message"><?= $errors['password'] ?></div><?php endif; ?>

</div>



<div class="form-group">

<span class="icon">

<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>

</span>

<input type="password" id="confirm_password" name="confirm_password" class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required minlength="8" placeholder=" ">

<label for="confirm_password" class="form-label">Confirme sua senha</label>

<?php if (isset($errors['confirm_password'])): ?><div class="error-message"><?= $errors['confirm_password'] ?></div><?php endif; ?>

</div>



<button type="submit" class="submit-btn">Criar Conta</button>

</form>



<div class="login-link">

Já tem uma conta? <a href="login.php">Faça login</a>

</div>

</div>

</body>

</html>