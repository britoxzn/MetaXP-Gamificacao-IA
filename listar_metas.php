<?php
session_start();
header('Content-Type: application/json');

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Usuário não autenticado"]);
    exit();
}

// DADOS DE CONEXÃO (ajuste aqui)
$host = "sql212.infinityfree.com";
$user = "if0_38657243";
$password = "metaspessoais";
$database = "f0_38657243_plataforma_de_planejamento_metas_pessoais";

// Conexão
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Erro na conexão"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Busca metas (agora com o id)
$sql = "SELECT id, titulo, categoria, status FROM metas WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$metas = [];
while ($row = $result->fetch_assoc()) {
    $metas[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $metas,
    "recomendacao_ia" => "Experimente criar uma meta de leitura de 15 minutos por dia!"
]);

$stmt->close();
$conn->close();
?>
