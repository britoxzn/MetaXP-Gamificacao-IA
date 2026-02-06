<?php
// Inicia a sessão e define cabeçalhos
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Inclui o arquivo de conexão com o banco de dados
require 'conexao.php';

// Verifica se o usuário está autenticado (remova a simulação em produção)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Você precisa estar logado para criar um objetivo']);
    exit;
}

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método não permitido
    echo json_encode(['error' => 'Método não permitido. Use POST para criar um objetivo']);
    exit;
}

// Obtém e valida os dados JSON
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Formato de dados inválido. Verifique o JSON enviado']);
    exit;
}

// Validação dos campos obrigatórios
$requiredFields = ['title', 'category', 'deadline'];
foreach ($requiredFields as $field) {
    if (empty(trim($data[$field] ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => "O campo {$field} é obrigatório. Preencha todos os campos corretamente."]);
        exit;
    }
}

// Valida e formata a data
$deadline = null;
try {
    $deadline = new DateTime($data['deadline']);
    $deadline = $deadline->format('Y-m-d'); // Formato esperado para o banco de dados
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Data inválida. Use o formato YYYY-MM-DD']);
    exit;
}

// Sanitização dos inputs para prevenir XSS
$title = htmlspecialchars(trim($data['title']));
$category = htmlspecialchars(trim($data['category']));

// Inicia a criação do objetivo no banco de dados
try {
    // Prepara e executa a query de inserção do objetivo
    $stmt = $conn->prepare("INSERT INTO objetivos 
                          (user_id, titulo, categoria, data_limite, data_criacao) 
                          VALUES (?, ?, ?, ?, NOW())");
    
    $stmt->bind_param("isss", $_SESSION['user_id'], $title, $category, $deadline);
    
    if ($stmt->execute()) {
        // Resposta de sucesso com feedback gamificado
        http_response_code(201); // Criado com sucesso
        echo json_encode([
            'success' => true,
            'goal_id' => $stmt->insert_id, // ID do objetivo recém-criado
            'message' => 'Parabéns! Você adicionou um novo objetivo ao seu planejamento. Rumo ao sucesso!'
        ]);
    } else {
        throw new Exception($conn->error); // Se a execução falhar
    }
} catch (Exception $e) {
    // Log do erro para análise posterior (use um sistema de log mais robusto em produção)
    error_log('Erro ao adicionar objetivo: ' . $e->getMessage());
    
    // Resposta de erro amigável
    http_response_code(500); // Erro interno do servidor
    echo json_encode(['error' => 'Ops! Algo deu errado. Tente novamente mais tarde.']);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
