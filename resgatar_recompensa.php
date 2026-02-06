<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Usuário não autenticado"]);
    exit();
}

$userId = $_SESSION['user_id'];
$xpNecessario = $_POST['xp'] ?? 0;
$titulo = $_POST['titulo'] ?? '';

$conn = new mysqli("localhost", "root", "", "metaxp");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Erro de conexão com banco"]);
    exit();
}

$stmt = $conn->prepare("SELECT xp FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_assoc();
$xpAtual = $dados["xp"];

if ($xpAtual >= $xpNecessario) {
    $novoXP = $xpAtual - $xpNecessario;
    $stmt = $conn->prepare("UPDATE usuarios SET xp = ? WHERE id = ?");
    $stmt->bind_param("ii", $novoXP, $userId);
    $stmt->execute();

    // (opcional) log da recompensa resgatada
    $stmt = $conn->prepare("INSERT INTO recompensas_resgatadas (usuario_id, titulo, xp_gasto) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $userId, $titulo, $xpNecessario);
    $stmt->execute();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "XP insuficiente"]);
}
?>
