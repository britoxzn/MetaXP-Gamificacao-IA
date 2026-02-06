<?php
require 'conexao.php'; // Arquivo de conexão com o banco de dados

// Supondo que o ID do template seja dinâmico (pode ser obtido via GET ou sessão)
$id_template = isset($_GET['template_id']) ? $_GET['template_id'] : 1; // Default é 1, caso não seja passado na URL

// Prepara a consulta para pegar o conteúdo do template baseado no ID
$stmt = $conn->prepare("SELECT conteudo, nome_template FROM templates WHERE id = ?");
$stmt->bind_param("i", $id_template);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $template = $result->fetch_assoc();
    
    // Definindo o cabeçalho para exibição em HTML e garantindo a codificação UTF-8
    header('Content-Type: text/html; charset=utf-8');
    
    // Exibe o título do template com o nome customizado
    echo "<h1>Bem-vindo ao MetaXP - Sua jornada de metas e produtividade</h1>";
    echo "<h2>Template: " . htmlspecialchars($template['nome_template']) . "</h2>";
    
    // Exibindo o conteúdo do template, podendo ser uma página com dicas de metas ou plano de produtividade
    echo "<div class='template-conteudo'>";
    echo $template['conteudo'];
    echo "</div>";

    // Se o template incluir gamificação ou IA, podemos adicionar algo como:
    echo "<p>Progresso atual: <strong>80%</strong></p>"; // Exemplo de progresso fixo (pode ser dinâmico conforme a plataforma)
    echo "<p>Meta concluída? <strong>Sim</strong></p>"; // Exemplo de mensagem com base em gamificação
    
    // Exemplo de sugestão de próxima meta com IA (futura integração)
    echo "<p>Próxima Meta sugerida pela IA: <strong>Meditação por 10 minutos diários</strong></p>";
} else {
    echo "<p style='color: red;'>Template não encontrado para sua jornada de metas.</p>";
}

// Fechando a conexão e o statement
$stmt->close();
$conn->close();
?>
