<?php
header('Content-Type: application/json');
require '../config.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '🔒 Sessão expirada. Por favor, faça login novamente.']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Consulta tarefas do usuário com status e ordenação
    $stmt = $pdo->prepare("
        SELECT 
            id, titulo, descricao, status, prioridade, data_criacao, due_date, categoria
        FROM 
            tasks
        WHERE 
            user_id = ?
        ORDER BY 
            status ASC, due_date ASC
    ");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'tarefas' => $tasks,
        'xp_gerado' => 5, // Exemplo: XP por consultar tarefas
        'mensagem' => '✅ Tarefas carregadas com sucesso.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erro ao carregar suas tarefas. Tente novamente.',
        'error' => $e->getMessage()
    ]);
}
