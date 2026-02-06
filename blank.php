<?php
// conexão.php - Arquivo de conexão com o banco
$host = 'sql212.infinityfree.com';
$user = 'if0_38657243';
$pass = 'metaspessoais';
$db = 'if0_38657243_plataforma_de_planejamento_metas_pessoais';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>