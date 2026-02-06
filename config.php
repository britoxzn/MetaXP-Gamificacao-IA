<?php
// config.php
$pdo = new PDO('mysql:host=localhost;dbname=seubanco;charset=utf8', 'usuario', 'senha');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cabeçalhos padrão para a API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

// Configurações do banco de dados
define("DB_HOST", "sql212.infinityfree.com");
define("DB_USER", "if0_38657243");
define("DB_PASS", "metaspessoais");
define("DB_NAME", "if0_38657243_plataforma_de_planejamento_metas_pessoais");

// Função para criar a conexão com o banco
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde."
        ]);
        exit();
    }

    $conn->set_charset("utf8");
    return $conn;
}

// Função para realizar consultas seguras (prepared statements)
function executeQuery($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao preparar consulta: " . $conn->error
        ]);
        exit();
    }

    if (!empty($params)) {
        // Suporte para diferentes tipos de dados (strings, inteiros, etc.)
        $types = str_repeat('s', count($params)); // Assume strings por padrão, pode ser ajustado para outros tipos
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// Função de gamificação: Gerar progresso com base nas metas
function calcularProgresso($usuario_id) {
    global $conn;

    // Buscar todas as metas do usuário
    $query = "SELECT status, COUNT(*) AS total FROM metas WHERE usuario_id = ? GROUP BY status";
    $params = [$usuario_id];
    $result = executeQuery($conn, $query, $params);

    $status_contagem = ['concluida' => 0, 'pendente' => 0, 'em_progresso' => 0];
    
    // Processar o resultado
    while ($row = $result->fetch_assoc()) {
        if (isset($status_contagem[$row['status']])) {
            $status_contagem[$row['status']] = $row['total'];
        }
    }

    // Calcular o progresso percentual (exemplo simples)
    $total_metas = array_sum($status_contagem);
    $progresso_percentual = $total_metas > 0 ? ($status_contagem['concluida'] / $total_metas) * 100 : 0;

    return round($progresso_percentual, 2);  // Retorna o progresso como porcentagem
}

// Função para gerar uma recomendação personalizada baseada em IA
function recomendarMetaIA($usuario_id) {
    global $conn;

    // Buscar metas passadas para sugerir novas, por exemplo
    $query = "SELECT descricao FROM metas WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 1";
    $params = [$usuario_id];
    $result = executeQuery($conn, $query, $params);

    if ($result->num_rows > 0) {
        $ultima_meta = $result->fetch_assoc();
        $descricao_meta = $ultima_meta['descricao'];

        // Recomendar uma meta baseada na última (exemplo simples)
        return "Com base na sua última meta: '$descricao_meta', recomendamos que você defina uma nova meta de progressão, como 'Aumentar a intensidade da tarefa'.";
    } else {
        // Se não houver metas anteriores, sugerir algo genérico
        return "Defina sua primeira meta para começar a jornada de produtividade!";
    }
}

?>
