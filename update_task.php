<?php
// MetaXP - Sistema de atualização de progresso de metas com gamificação

header('Content-Type: application/json');
require '../config.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Coleta e valida os dados enviados
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || !isset($data['status'])) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

try {
    // Atualiza o status da meta (tarefa)
    $updateTask = $pdo->prepare("
        UPDATE tasks 
        SET status = ? 
        WHERE id = ? AND user_id = ?
    ");
    $updateTask->execute([
        $data['status'],
        $data['id'],
        $_SESSION['user_id']
    ]);

    // Sistema de Recompensa: adiciona pontos de experiência se a meta foi concluída
    if ($data['status'] === 'completed') {
        $gainXP = $pdo->prepare("
            UPDATE users 
            SET xp_points = xp_points + 100 
            WHERE id = ?
        ");
        $gainXP->execute([$_SESSION['user_id']]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao atualizar meta: ' . $e->getMessage()]);
}
?>
