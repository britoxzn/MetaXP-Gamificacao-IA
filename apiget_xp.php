<?php
// Inicie a sessão no início do arquivo
session_start();

header('Content-Type: application/json');
require '../includes/config.php';

// Debug: Verifique se a sessão está funcionando
error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'N/D'));

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Acesso não autorizado',
        'message' => 'Sessão expirada ou não autenticado',
        'redirect' => 'login.php'
    ]);
    exit;
}

// Aqui a lógica PHP pode continuar com a recuperação de dados e lógica da plataforma

?>