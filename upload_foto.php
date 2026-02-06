<?php
session_start();

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verifique se uma foto foi enviada
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    // Configuração do diretório de upload
    $diretorio = "uploads/perfis/";
    $fotoTemp = $_FILES['foto_perfil']['tmp_name'];
    $fotoNome = $_FILES['foto_perfil']['name'];
    $fotoExt = pathinfo($fotoNome, PATHINFO_EXTENSION);
    $fotoNovoNome = uniqid() . '.' . $fotoExt;

    // Movendo a foto para o diretório de upload
    if (move_uploaded_file($fotoTemp, $diretorio . $fotoNovoNome)) {
        // Atualize o caminho da foto no banco de dados (supondo que você tenha uma tabela de usuários)
        // Exemplo de como fazer isso (supondo que você tenha uma conexão $conn):
        $userId = $_SESSION['user_id'];
        $fotoUrl = $diretorio . $fotoNovoNome;

        // Atualize a foto no banco de dados
        $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fotoUrl, $userId);
        $stmt->execute();

        // Redireciona de volta para o dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Erro ao fazer upload da foto.";
    }
} else {
    echo "Nenhuma foto enviada ou ocorreu um erro no upload.";
}
?>
