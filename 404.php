<?php
// Conecta ao banco de dados
$conn = new mysqli("sql212.infinityfree.com", "if0_38657243", "metaspessoais", "if0_38657243_plataforma_de_planejamento_metas_pessoais");

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Busca o conteúdo da página 404, adaptando para o projeto MetaXP
$result = $conn->query("SELECT conteudo FROM paginas WHERE rota = '404'");
if ($result->num_rows > 0) {
    // Pega o conteúdo da página 404
    $row = $result->fetch_assoc();
    $conteudo = $row['conteudo'];
} else {
    // Caso não tenha conteúdo específico, exibe uma mensagem padrão
    $conteudo = "
        <div class='text-center'>
            <div class='error mx-auto' data-text='404'>404</div>
            <p class='lead text-gray-800 mb-5'>Ops! Parece que você se perdeu em uma missão.</p>
            <p class='text-gray-500 mb-0'>Não conseguimos encontrar essa meta. Talvez você tenha se desviado do caminho!</p>
            <a href='index.php' class='btn btn-primary'>&larr; Voltar para o Painel de Metas</a>
            <div class='badge'>Recupere sua Conquista!</div>
        </div>";
}

// Exibe o conteúdo da página 404
echo $conteudo;

// Fecha a conexão com o banco de dados
$conn->close();
?>
