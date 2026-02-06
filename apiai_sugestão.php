<?php
// Define cabeçalhos HTTP
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff'); // Previne MIME type sniffing
header('Cache-Control: no-cache, must-revalidate'); // Evita cache

// Inicia a sessão e inclui configurações do MetaXP
session_start();
require '../includes/config.php';

// Verifica autenticação do usuário
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'error' => 'Acesso não autorizado',
        'message' => 'Você precisa estar logado para receber sugestões de metas personalizadas. Vamos lá, entre no MetaXP!'
    ]);
    exit;
}

// Banco de sugestões personalizadas baseadas no comportamento de gamificação e IA
$suggestions = [
    [
        "text" => "🎯 Que tal estabelecer uma meta de leitura diária de 30 minutos? O conhecimento é poder!",
        "category" => "Desenvolvimento Pessoal",
        "icon" => "📚"
    ],
    [
        "text" => "🚀 Aprenda algo novo esta semana! Defina uma meta para adquirir uma nova habilidade.",
        "category" => "Aprendizado",
        "icon" => "🎓"
    ],
    [
        "text" => "💪 Vamos ficar em forma? Que tal uma meta de exercícios para os próximos dias?",
        "category" => "Saúde",
        "icon" => "💪"
    ],
    [
        "text" => "🧹 Organize seu espaço de trabalho como parte de sua meta para o dia. Produtividade é a chave!",
        "category" => "Produtividade",
        "icon" => "🧹"
    ],
    [
        "text" => "💰 Meta financeira: que tal começar a economizar um pouco mais este mês? Planejamento financeiro é essencial!",
        "category" => "Finanças",
        "icon" => "💰"
    ],
    [
        "text" => "🍎 Planeje uma refeição saudável para amanhã! Nutrição e bem-estar andam juntos.",
        "category" => "Alimentação",
        "icon" => "🍎"
    ],
    [
        "text" => "👨‍👩‍👧‍👦 Defina uma meta de passar mais tempo de qualidade com sua família. Isso vale pontos no MetaXP!",
        "category" => "Relacionamentos",
        "icon" => "👨‍👩‍👧‍👦"
    ]
];

// Seleciona uma sugestão aleatória do banco de sugestões personalizadas
$randomSuggestion = $suggestions[array_rand($suggestions)];

// Resposta formatada com feedback gamificado
http_response_code(200);
echo json_encode([
    'success' => true,
    'suggestion' => $randomSuggestion['text'],
    'category' => $randomSuggestion['category'],
    'icon' => $randomSuggestion['icon'],
    'timestamp' => date('Y-m-d H:i:s'),
    'game_feedback' => [
        'message' => 'Você está progredindo no MetaXP! A cada meta que alcançar, você ganha mais pontos e recompensas.',
        'next_steps' => 'Continue definindo metas para conquistar recompensas e avançar no seu planejamento!'
    ]
], JSON_UNESCAPED_UNICODE);
