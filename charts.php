<?php
// conexao.php
$host = 'sql212.infinityfree.com';
$user = 'if0_38657243'; 
$pass = 'metaspessoais';
$db = 'if0_38657243_plataforma_de_planejamento_metas_pessoais';
require 'conexao.php';


// Preparar e executar a query
$stmt = $conn->prepare("INSERT INTO templates (nome, conteudo, tipo) VALUES (?, ?, ?)");
$nome = "Dashboard de Produtividade MetaXP";
$tipo = "dashboard_produtividade";

// Vinculando parâmetros
$stmt->bind_param("sss", $nome, $html_template, $tipo);

// Executar e retornar a resposta
if ($stmt->execute()) {
    echo "✅ Template inserido com sucesso! ID: " . $stmt->insert_id;
} else {
    echo "❌ Erro ao inserir template: " . $stmt->error;
}

// Fechar a conexão
$stmt->close();
$conn->close();
?>