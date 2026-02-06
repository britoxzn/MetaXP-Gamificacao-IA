<?php
// Cabeçalhos para JSON e CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

// Conexão com banco de dados
$host = "sql212.infinityfree.com";
$user = "if0_38657243";
$pass = "metaspessoais";
$db = "if0_38657243_plataforma_de_planejamento_metas_pessoais";

$conn = new mysqli($host, $user, $pass, $db);

// Verificação da conexão
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Erro na conexão com o banco de dados"]);
    exit();
}

$conn->set_charset("utf8");

// Função de consulta segura
function safe_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Erro ao preparar consulta"]);
        exit();
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// Função para sugestão da IA
function ia_recomendar_meta($usuario_id) {
    $sugestoes = [
        "Meditar por 10 minutos",
        "Completar 3 tarefas prioritárias",
        "Ler 30 páginas de um livro de produtividade",
        "Planejar a semana no domingo à noite",
        "Evitar redes sociais por 1 hora após acordar"
    ];

    return $sugestoes[array_rand($sugestoes)];
}

// Consultar metas do usuário (ID fixo aqui como exemplo)
$usuario_id = 1;

$query = "SELECT titulo, categoria, status FROM metas WHERE usuario_id = ? ORDER BY data_criacao DESC";
$result = safe_query($conn, $query, [$usuario_id]);

$metas = $result->fetch_all(MYSQLI_ASSOC);

// Sugestão da IA
$sugestao = ia_recomendar_meta($usuario_id);

// Retornar resposta JSON
echo json_encode([
    "status" => "success",
    "data" => $metas,
    "recomendacao_ia" => $sugestao
], JSON_UNESCAPED_UNICODE);

// Fechar conexão
$conn->close();
?>
