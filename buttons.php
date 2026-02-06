<?php
// Configuração da conexão com o banco
$host = 'sql212.infinityfree.com';
$user = 'if0_38657243';
$pass = 'metaspessoais';
$db = 'if0_38657243_plataforma_de_planejamento_metas_pessoais';

// Conectar ao banco
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Conteúdo HTML básico
$html = '<!DOCTYPE html><html><head><title>Botões</title></head><body>Conteúdo da página</body></html>';

// Inserir no banco
$sql = "INSERT INTO paginas (titulo, conteudo, rota) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$titulo = "Página de Botões - MetaXP";
$rota = "buttons";

$stmt->bind_param("sss", $titulo, $html, $rota);

if ($stmt->execute()) {
    echo "Página criada! ID: " . $stmt->insert_id;
} else {
    echo "Erro: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>