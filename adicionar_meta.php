<?php
require 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

$id_usuario = $data->id_usuario ?? null;
$titulo = $data->titulo ?? null;
$categoria = $data->categoria ?? null;
$data_limite = $data->data_limite ?? null;
$status = $data->status ?? 'pendente';
$dificuldade = $data->dificuldade ?? 'média';

if (!$id_usuario || !$titulo || !$categoria || !$data_limite) {
    echo json_encode([
        "success" => false,
        "message" => "Campos obrigatórios não foram preenchidos corretamente."
    ]);
    exit;
}

$sql = "INSERT INTO objetivos (id_usuario, titulo, categoria, data_limite, status, dificuldade) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssss", $id_usuario, $titulo, $categoria, $data_limite, $status, $dificuldade);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Meta adicionada com sucesso!"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao adicionar a meta. Tente novamente."
    ]);
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Nova Meta - MetaXP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }
        h1 {
            color: #2e7d32;
            text-align: center;
        }
        .form-container {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #2e7d32;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #27662a;
        }
        #result {
            margin-top: 15px;
            padding: 12px;
            border-radius: 6px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            display: block;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            display: block;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Nova Meta - MetaXP</h1>
        <form id="metaForm">
            <div class="form-group">
                <label for="id_usuario">ID do Usuário:</label>
                <input type="number" id="id_usuario" name="id_usuario" required>
            </div>
            <div class="form-group">
                <label for="titulo">Título da Meta:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <option value="Pessoal">Pessoal</option>
                    <option value="Profissional">Profissional</option>
                    <option value="Saúde">Saúde e Bem-Estar</option>
                    <option value="Educação">Educação</option>
                    <option value="Financeiro">Financeiro</option>
                    <option value="Criatividade">Criatividade</option>
                </select>
            </div>
            <div class="form-group">
                <label for="data_limite">Data Limite:</label>
                <input type="date" id="data_limite" name="data_limite" required>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="pendente">Pendente</option>
                    <option value="em andamento">Em Andamento</option>
                    <option value="concluída">Concluída</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dificuldade">Dificuldade:</label>
                <select id="dificuldade" name="dificuldade">
                    <option value="fácil">Fácil</option>
                    <option value="média">Média</option>
                    <option value="difícil">Difícil</option>
                </select>
            </div>
            <button type="submit">Salvar Meta</button>
        </form>
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('metaForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = {
                id_usuario: document.getElementById('id_usuario').value,
                titulo: document.getElementById('titulo').value,
                categoria: document.getElementById('categoria').value,
                data_limite: document.getElementById('data_limite').value,
                status: document.getElementById('status').value,
                dificuldade: document.getElementById('dificuldade').value
            };

            fetch('criar_meta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                const result = document.getElementById('result');
                result.className = data.success ? 'success' : 'error';
                result.textContent = data.message;
                result.style.display = 'block';

                if (data.success) {
                    document.getElementById('metaForm').reset();
                }
            })
            .catch(() => {
                const result = document.getElementById('result');
                result.className = 'error';
                result.textContent = 'Erro ao conectar com o servidor.';
                result.style.display = 'block';
            });
        });
    </script>
</body>
</html>
