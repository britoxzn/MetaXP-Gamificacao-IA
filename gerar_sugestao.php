<?php
require 'config.php';

// Recebe os dados enviados via JSON
$data = json_decode(file_get_contents("php://input"), true);
$id_usuario = $data['id_usuario'] ?? null;
$contexto = $data['contexto'] ?? null;

// Verificação básica dos dados recebidos
if (!$id_usuario || !$contexto) {
    echo json_encode(["success" => false, "message" => "Dados incompletos para gerar sugestão."]);
    exit;
}

// Simulação de sugestão baseada em IA (poderia ser substituído por chamada à OpenAI API)
$sugestoesIA = [
    "meta" => "🎯 Experimente definir metas SMART (Específicas, Mensuráveis, Atingíveis, Relevantes e Temporais) para mais clareza e foco.",
    "tarefa" => "🛠️ Divida grandes tarefas em subtarefas que levem no máximo 30 minutos. Isso aumenta sua motivação e produtividade.",
    "produtividade" => "⏱️ Use a Técnica Pomodoro: 25 minutos de foco total + 5 minutos de pausa. A cada 4 ciclos, faça uma pausa maior!"
];

// Sugestão padrão caso o contexto não seja reconhecido
$sugestao = $sugestoesIA[$contexto] ?? "🤖 Ainda estou aprendendo sobre esse contexto. Em breve, terei sugestões personalizadas para você.";

// Armazena a sugestão gerada no banco de dados para uso em análises ou gamificação
$sql = "INSERT INTO ia_sugestoes (id_usuario, sugestao, contexto, data_gerada) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $id_usuario, $sugestao, $contexto);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "sugestao" => $sugestao,
        "xp_bonus" => 10, // Exemplo: poderia dar XP extra por interagir com a IA
        "message" => "Sugestão gerada com sucesso! 🎉"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao registrar sugestão. Tente novamente em breve."
    ]);
}

$stmt->close();
$conn->close();
