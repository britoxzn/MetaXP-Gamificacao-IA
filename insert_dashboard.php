<?php
require 'conexao.php';

// Verificar a conexão com o banco de dados
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Preparar a query
$stmt = $conn->prepare("INSERT INTO templates 
                        (nome, descricao, conteudo, tipo, rota, versao) 
                        VALUES (?, ?, ?, ?, ?, ?)");

// Verificar se a query foi preparada corretamente
if ($stmt === false) {
    die("Erro na preparação da query: " . $conn->error);
}

// Parâmetros
$nome = "MetaXP - Dashboard Principal";
$descricao = "Template principal do dashboard da plataforma de metas e produtividade com gamificação e IA";
$tipo = "dashboard";
$rota = "usuario/dashboard";
$versao = "1.0.0";

// Vincular os parâmetros
$stmt->bind_param("ssssss", $nome, $descricao, $html, $tipo, $rota, $versao);

// Executar a query
if ($stmt->execute()) {
    echo "Template de dashboard inserido com sucesso! ID: " . $stmt->insert_id;
} else {
    echo "Erro ao inserir template: " . $stmt->error;
}

// Fechar a query e a conexão
$stmt->close();
$conn->close();
?>
