<?php
// Configurações de cabeçalho
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0');

// Inicia sessão e inclui configurações
session_start();
require '../includes/config.php';

// Verifica autenticação do usuário
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'error' => 'Não autenticado',
        'message' => 'Você precisa fazer login para acessar suas metas na MetaXP'
    ]);
    exit;
}

// Valida método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    // Consulta para buscar metas não completadas
    $stmt = $pdo->prepare("
        SELECT 
            id,
            titulo AS title,
            descricao AS description,
            categoria AS category,
            DATE_FORMAT(data_limite, '%Y-%m-%d') AS deadline,
            DATE_FORMAT(data_criacao, '%Y-%m-%d') AS created_at,
            prioridade AS priority
        FROM 
            metas
        WHERE 
            usuario_id = ? 
            AND completada = 0
        ORDER BY 
            CASE 
                WHEN prioridade = 'alta' THEN 1
                WHEN prioridade = 'media' THEN 2
                WHEN prioridade = 'baixa' THEN 3
                ELSE 4
            END,
            data_limite ASC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatação das datas e status das metas
    $hoje = new DateTime();
    foreach ($metas as &$meta) {
        $meta['status'] = 'pendente'; // Status padrão
        if (!empty($meta['deadline'])) {
            $dataLimite = new DateTime($meta['deadline']);
            $meta['dias_restantes'] = $hoje->diff($dataLimite)->days;
            if ($dataLimite < $hoje) {
                $meta['status'] = 'atrasada'; // Meta atrasada
            }
        }
        
        // Adicionando ícone para categorias
        switch ($meta['category']) {
            case 'desenvolvimento pessoal':
                $meta['icon'] = '📚';
                break;
            case 'aprendizado':
                $meta['icon'] = '🎓';
                break;
            case 'saúde':
                $meta['icon'] = '💪';
                break;
            case 'produtividade':
                $meta['icon'] = '🧹';
                break;
            case 'finanças':
                $meta['icon'] = '💰';
                break;
            case 'alimentação':
                $meta['icon'] = '🍎';
                break;
            case 'relacionamentos':
                $meta['icon'] = '👨‍👩‍👧‍👦';
                break;
            default:
                $meta['icon'] = '❓'; // Caso não tenha categoria definida
                break;
        }
    }

    // Resposta de sucesso com dados das metas
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $metas,
        'count' => count($metas),
        'message' => 'Aqui estão suas metas pendentes na MetaXP!',
        'retrieved_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Log do erro (em produção, use um sistema de log adequado)
    error_log('Erro ao buscar metas: ' . $e->getMessage());
    
    // Resposta de erro
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao carregar suas metas',
        'message' => 'Houve um erro ao tentar carregar suas metas. Tente novamente mais tarde.'
    ]);
}
?>
