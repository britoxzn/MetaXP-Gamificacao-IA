<?php
// Inicia a sessão e define o cabeçalho como JSON
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff'); // Previne MIME type sniffing

// Inclui o arquivo de configuração
require '../config.php';

// Verifica autenticação do usuário
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Você precisa estar logado para criar uma nova meta']);
    exit;
}

// Valida o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método não permitido. Use POST para criar uma nova meta']);
    exit;
}

// Obtém e valida os dados da requisição
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Formato de dados inválido. Certifique-se de enviar JSON corretamente']);
    exit;
}

// Validação dos campos obrigatórios
if (empty(trim($data['title'] ?? ''))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'O título da sua meta é obrigatório']);
    exit;
}

// Valida a prioridade
$allowedPriorities = ['low', 'medium', 'high'];
$priority = $data['priority'] ?? 'medium';
if (!in_array($priority, $allowedPriorities)) {
    $priority = 'medium';
}

// Valida a categoria (se fornecida)
$allowedCategories = ['productivity', 'health', 'learning', 'well-being', 'financial'];
$category = $data['category'] ?? 'productivity';
if (!in_array($category, $allowedCategories)) {
    $category = 'productivity'; // Categoria padrão
}

// Valida a data de conclusão (se fornecida)
$dueDate = null;
if (!empty($data['due_date'])) {
    $dueDate = DateTime::createFromFormat('Y-m-d', $data['due_date']);
    if (!$dueDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de data inválido. Use o formato YYYY-MM-DD']);
        exit;
    }
    $dueDate = $dueDate->format('Y-m-d');
}

try {
    // Prepara e executa a query para inserir a nova meta no banco de dados
    $stmt = $pdo->prepare("INSERT INTO tasks 
                          (user_id, title, description, priority, category, due_date, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $_SESSION['user_id'],
        htmlspecialchars(trim($data['title'])),
        htmlspecialchars(trim($data['description'] ?? 'Descrição não fornecida')),
        $priority,
        $category,
        $dueDate
    ]);
    
    // Resposta de sucesso com uma mensagem mais envolvente
    http_response_code(201); // Created
    echo json_encode([
        'success' => true, 
        'task_id' => $pdo->lastInsertId(),
        'message' => 'Parabéns! Sua meta foi criada com sucesso e está pronta para ser conquistada!'
    ]);
    
} catch (PDOException $e) {
    // Log do erro (em produção, use um sistema de log adequado)
    error_log('Erro ao adicionar meta: ' . $e->getMessage());
    
    // Resposta de erro com uma mensagem mais acolhedora
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Ocorreu um erro ao adicionar sua meta. Tente novamente mais tarde']);
}
