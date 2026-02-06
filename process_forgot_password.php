<?php
session_start();
require 'conexao.php'; // Certifique-se de que o caminho está correto

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza e valida o e-mail
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Consulta para verificar se o e-mail existe no banco de dados
            $sql = "SELECT id FROM usuarios WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Gerar um token de redefinição (você pode ajustar isso conforme necessário)
                $token = bin2hex(random_bytes(50)); // Gerando um token único
                
                // Lógica de envio de e-mail de redefinição (ajuste conforme sua necessidade)

                // Defina a mensagem de sucesso na sessão
                $_SESSION['sucesso'] = "Link de redefinição enviado com sucesso para o seu e-mail!";
                
                // Redireciona para a página de sucesso
                header("Location: sucesso.php");
                exit(); // Garante que o script pare aqui
            } else {
                // Se o e-mail não for encontrado
                $_SESSION['erro'] = "E-mail não encontrado.";
                header("Location: forgot-password.php"); // Redireciona de volta para a página de recuperação de senha
                exit();
            }
        } catch (PDOException $e) {
            // Se houver erro na consulta ao banco de dados, exibe o erro
            $_SESSION['erro'] = "Erro ao acessar o banco de dados: " . $e->getMessage();
            header("Location: forgot-password.php"); // Redireciona de volta com erro
            exit();
        }
    } else {
        $_SESSION['erro'] = "E-mail inválido.";
        header("Location: forgot-password.php"); // Redireciona com erro
        exit();
    }
}
?>
