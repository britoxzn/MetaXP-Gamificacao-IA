<?php
session_start();
require 'conexao.php'; // Certifique-se de que este arquivo conecta corretamente ao banco

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        echo "Preencha todos os campos.";
        exit();
    }

    // Verifica o usuário no banco
    $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verifica a senha
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['nome'];

            // Redireciona para o index.php
            header("Location: index.php");
            exit();
        } else {
            echo "Senha incorreta.";
        }
    } else {
        echo "Usuário não encontrado.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método de requisição inválido.";
}
?>
