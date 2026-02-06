<?php
require_once 'conexao.php'; // Conecta com o banco (ajuste o caminho se necessário)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    // Verifica se o e-mail existe no sistema
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Remove tokens anteriores (opcional)
        $pdo->prepare("DELETE FROM tokens_recuperacao WHERE user_id = ?")->execute([$usuario['id']]);

        // Insere novo token
        $pdo->prepare("INSERT INTO tokens_recuperacao (user_id, token, expira_em) VALUES (?, ?, ?)")
            ->execute([$usuario['id'], $token, $expira_em]);

        // Envia o link por e-mail (simulado)
        $link = "http://localhost/resetar_senha.php?token=$token";

        // Aqui você pode usar PHPMailer ou mail()
        echo "<div style='padding:20px; font-family:Arial; max-width:600px; margin:auto;'>";
        echo "<h3>🔐 Link de recuperação enviado!</h3>";
        echo "<p>Olá! Recebemos uma solicitação para redefinir sua senha.</p>";
        echo "<p><strong>Link (válido por 1 hora):</strong></p>";
        echo "<a href='$link'>$link</a>";
        echo "<hr><p style='font-size:0.9em;'>Se você não solicitou, ignore este e-mail.</p>";
        echo "</div>";
    } else {
        echo "<script>alert('E-mail não encontrado.'); window.location.href='forgot-password.php';</script>";
    }
}
?>
