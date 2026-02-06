<?php
session_start();

// Conexão com o banco de dados
$host = 'sql212.infinityfree.com';
$db   = 'if0_38657243_plataforma_de_planejamento_metas_pessoais';
$user = 'if0_38657243';
$pass = 'metaspessoais';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Pega os dados do formulário
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Verifica se campos estão preenchidos
if (empty($username) || empty($email) || empty($password)) {
    $_SESSION['mensagem'] = "Preencha todos os campos!";
    header("Location: register.php");
    exit();
}

// Verifica se o e-mail já está cadastrado
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $_SESSION['mensagem'] = "Este e-mail já está cadastrado!";
    header("Location: register.php");
    exit();
}

// Criptografa a senha
$senhaSegura = password_hash($password, PASSWORD_DEFAULT);

// Insere no banco de dados
$stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

try {
    $stmt->execute([$username, $email, $senhaSegura]);
    $_SESSION['mensagem'] = "Cadastro realizado com sucesso! Faça login.";
    header("Location: login.php");
    exit();
} catch (Exception $e) {
    $_SESSION['mensagem'] = "Erro ao cadastrar usuário: " . $e->getMessage();
    header("Location: register.php");
    exit();
}
?>
