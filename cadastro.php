<?php
require 'config.php';

// Lê e decodifica os dados JSON recebidos
$data = json_decode(file_get_contents("php://input"));

// Validações básicas
if (!isset($data->nome, $data->email, $data->senha)) {
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit;
}

$nome = trim($data->nome);
$email = trim($data->email);
$senha = trim($data->senha);

// Verifica se os campos estão vazios
if (empty($nome) || empty($email) || empty($senha)) {
    echo json_encode(["success" => false, "message" => "Preencha todos os campos para iniciar sua jornada de metas!"]);
    exit;
}

// Valida formato de e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "E-mail inválido. Certifique-se de que seu e-mail esteja correto para acessar suas metas!"]);
    exit;
}

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

// Prepara e executa a query
$sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(["success" => false, "message" => "Erro na preparação da query. Estamos tendo problemas para te cadastrar. Tente novamente!"]);
    exit;
}

$stmt->bind_param("sss", $nome, $email, $senhaHash);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cadastro realizado com sucesso! Agora você pode começar a criar suas metas no MetaXP. Vamos lá!"]);
} else {
    echo json_encode(["success" => false, "message" => "Erro no cadastro. Algo deu errado ao tentar cadastrar seu perfil: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
