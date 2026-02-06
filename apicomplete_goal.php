<?php
// Configurações de cabeçalho
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, must-revalidate');

// Inicia sessão e inclui configurações
session_start();
require '../includes/config.php';

// Verifica autenticação do usuário
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'error' => 'Não autenticado',
        'message' => 'Você precisa estar logado para completar suas metas na MetaXP'
    ]);
    exit;
}

// Valida método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obtém e valida dados da requisição
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID da meta não fornecido ou inválido']);
    exit;
}

try {
    // Inicia transação para garantir a integridade dos dados
    $pdo->beginTransaction();

    // 1. Marcar meta como completada
    $stmt = $pdo->prepare("UPDATE metas 
                          SET completada = 1, 
                              data_conclusao = NOW() 
                          WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$data['id'], $_SESSION['user_id']]);

    $rowsAffected = $stmt->rowCount();

    if ($rowsAffected === 0) {
        throw new Exception('Meta não encontrada ou já completada');
    }

    // 2. Adicionar XP ao usuário (100 XP por meta completada)
    $xpToAdd = 100;
    $stmt = $pdo->prepare("UPDATE usuarios 
                          SET xp_total = xp_total + ?, 
                              xp_atual = xp_atual + ? 
                          WHERE id = ?");
    $stmt->execute([$xpToAdd, $xpToAdd, $_SESSION['user_id']]);

    // 3. Verificar e atualizar nível do usuário
    $stmt = $pdo->prepare("SELECT xp_total FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();

    // Definir o XP necessário para o próximo nível
    $xpNecessarioPorNivel = 1000; 
    $novoNivel = floor($usuario['xp_total'] / $xpNecessarioPorNivel);

    // Verificar se o nível foi alterado
    $stmt = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $nivelAtual = $stmt->fetchColumn();

    if ($novoNivel > $nivelAtual) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nivel = ? WHERE id = ?");
        $stmt->execute([$novoNivel, $_SESSION['user_id']]);
        $nivelSubiu = true;
    } else {
        $nivelSubiu = false;
    }

    // Commit da transação
    $pdo->commit();

    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Meta completada com sucesso! Você acaba de conquistar 100 XP.',
        'xp_ganho' => $xpToAdd,
        'nivel_subiu' => $nivelSubiu,
        'novo_nivel' => $nivelSubiu ? $novoNivel : null,
        'xp_atual' => $usuario['xp_total'] + $xpToAdd,
        'status' => $nivelSubiu ? "Parabéns! Você alcançou um novo nível!" : "Continue assim para alcançar o próximo nível."
    ]);

} catch (PDOException $e) {
    // Caso ocorra um erro, desfaz a transação e retorna mensagem
    $pdo->rollBack();
    error_log('Erro ao completar meta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao completar meta',
        'message' => 'Ocorreu um erro no servidor. Tente novamente mais tarde.'
    ]);
} catch (Exception $e) {
    // Se erro de validação, retorna uma resposta com a mensagem de erro
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
