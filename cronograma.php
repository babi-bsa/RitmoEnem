<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: home.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nome = $_SESSION['nome'];

// Processa a inserção/atualização de tarefas
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao == 'adicionar_tarefa') {
        $materia = $_POST['materia'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $duracao_minutos = intval($_POST['duracao_minutos'] ?? 30);
        $horario_inicio = $_POST['horario_inicio'] ?? '';
        $horario_fim = $_POST['horario_fim'] ?? '';
        $data = $_POST['data'] ?? date('Y-m-d');
        
        // Validação básica
        if ($materia && $horario_inicio && $horario_fim && strtotime($horario_inicio) < strtotime($horario_fim)) {
            $stmt = $conn->prepare("INSERT INTO tarefas (usuario_id, materia, descricao, duracao_minutos, horario_inicio, horario_fim, data) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isisiss", $usuario_id, $materia, $descricao, $duracao_minutos, $horario_inicio, $horario_fim, $data);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Busca as tarefas do usuário
$data_filtro = $_GET['data'] ?? date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM tarefas WHERE usuario_id = ? AND data = ? ORDER BY horario_inicio ASC");
$stmt->bind_param("is", $usuario_id, $data_filtro);
$stmt->execute();
$result = $stmt->get_result();
$tarefas = [];
while ($row = $result->fetch_assoc()) {
    $tarefas[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Cronograma - Ritmo Enem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Kodchasan:wght@400;600&family=Klee+One&family=Limelight&family=Linden+Hill&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-beige: #f3f3e0;
            --color-darkcyan: #0097b2;
            --color-linen: #f2efe7;
            --color-darkslateblue: #133e87;
            --color-darkslategray: #16404d;
            --color-black: #000;
            --color-success: #27ae60;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;

            --font-inter: 'Inter', sans-serif;
            --font-limelight: 'Limelight', cursive;
            --font-kodchasan: 'Kodchasan', sans-serif;
            --font-klee-one: 'Klee One', cursive;
            --font-linden-hill: 'Linden Hill', serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-inter);
            background-color: var(--color-beige);
            color: var(--color-darkslategray);
            min-height: 100vh;
        }

        header {
            background-color: var(--color-beige);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--color-darkslategray);
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-family: var(--font-limelight);
            font-size: 32px;
            color: var(--color-darkcyan);
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        nav a {
            color: var(--color-darkslategray);
            text-decoration: none;
            font-family: var(--font-kodchasan);
            font-weight: 600;
            transition: color 0.3s ease;
        }

        nav a:hover,
        nav a.active {
            color: var(--color-darkcyan);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: var(--font-kodchasan);
        }

        .user-info a {
            background-color: var(--color-darkcyan);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .user-info a:hover {
            background-color: #007a91;
        }

        .page-header {
            max-width: 1200px;
            margin: 40px auto 0;
            padding: 0 20px;
            text-align: center;
        }

        .page-header h1 {
            font-family: var(--font-limelight);
            font-size: 48px;
            color: var(--color-darkslateblue);
            margin-bottom: 10px;
        }

        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .container-flex {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .form-panel {
            flex: 0 0 350px;
            background-color: var(--color-linen);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .form-panel h3 {
            font-family: var(--font-kodchasan);
            font-size: 20px;
            color: var(--color-darkslateblue);
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-family: var(--font-kodchasan);
            font-weight: 600;
            color: var(--color-darkslategray);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            font-family: var(--font-inter);
            color: var(--color-darkslategray);
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 70px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--color-darkcyan);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-family: var(--font-kodchasan);
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #007a91;
        }

        .schedule-panel {
            flex: 1;
        }

        .date-selector {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-selector input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            font-family: var(--font-inter);
            color: var(--color-darkslategray);
        }

        .date-selector button {
            padding: 8px 16px;
            background-color: var(--color-darkcyan);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: var(--font-kodchasan);
            transition: background 0.3s;
        }

        .date-selector button:hover {
            background-color: #007a91;
        }

        .schedule-container {
            background-color: var(--color-linen);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .schedule-grid {
            display: grid;
            gap: 1px;
            background-color: #ddd;
            border: 2px solid var(--color-darkslategray);
            border-radius: 10px;
            overflow: hidden;
        }

        .schedule-hour {
            background-color: white;
            padding: 15px;
            min-height: 80px;
            border-right: 2px solid #ddd;
            position: relative;
        }

        .schedule-hour:last-child {
            border-right: none;
        }

        .hour-label {
            font-family: var(--font-kodchasan);
            font-weight: 600;
            color: var(--color-darkslateblue);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .task-block {
            background: linear-gradient(135deg, var(--color-darkcyan), #0077a3);
            color: white;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            font-family: var(--font-kodchasan);
            margin-bottom: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .task-block-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .task-block-time {
            font-size: 11px;
            opacity: 0.9;
        }

        .no-tasks {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-family: var(--font-klee-one);
            font-size: 16px;
        }

        .stats-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e8f4f8;
            border-left: 4px solid var(--color-darkcyan);
            border-radius: 8px;
            font-family: var(--font-inter);
            font-size: 14px;
            color: var(--color-darkslategray);
        }

        footer {
            background-color: var(--color-darkcyan);
            color: white;
            text-align: center;
            padding: 20px 10px;
            font-family: var(--font-inter);
            margin-top: 80px;
        }

        @media (max-width: 1000px) {
            .container-flex {
                flex-direction: column;
            }

            .form-panel {
                flex: 1;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php">HOME</a>
        <a href="cronograma.php" class="active">CRONOGRAMA</a>
        <a href="sobre.php">SOBRE NÓS</a>
        <a href="#">FÓRUM ONLINE</a>
        <div class="user-info">
            <span><?php echo htmlspecialchars($nome); ?></span>
            <a href="logout.php">SAIR</a>
        </div>
    </nav>
</header>

<div class="page-header">
    <h1>Seu Cronograma</h1>
</div>

<div class="main-content">
    <div class="container-flex">
        <!-- Painel de Formulário -->
        <div class="form-panel">
            <h3>Adicionar Tarefa</h3>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="adicionar_tarefa">
                
                <div class="form-group">
                    <label for="data">Data:</label>
                    <input type="date" name="data" id="data" value="<?php echo $data_filtro; ?>" required>
                </div>

                <div class="form-group">
                    <label for="materia">Disciplina:</label>
                    <select name="materia" id="materia" required>
                        <option value="">Selecione uma disciplina</option>
                        <option value="Matemática">Matemática</option>
                        <option value="Português">Português</option>
                        <option value="História">História</option>
                        <option value="Geografia">Geografia</option>
                        <option value="Física">Física</option>
                        <option value="Química">Química</option>
                        <option value="Biologia">Biologia</option>
                        <option value="Inglês">Inglês</option>
                        <option value="Educação Física">Educação Física</option>
                        <option value="Artes">Artes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <textarea name="descricao" id="descricao" placeholder="Ex: Resolver exercícios de derivadas"></textarea>
                </div>

                <div class="form-group">
                    <label for="horario_inicio">Hora de Início:</label>
                    <input type="time" name="horario_inicio" id="horario_inicio" required>
                </div>

                <div class="form-group">
                    <label for="horario_fim">Hora de Término:</label>
                    <input type="time" name="horario_fim" id="horario_fim" required>
                </div>

                <div class="form-group">
                    <label for="duracao_minutos">Duração Aproximada (minutos):</label>
                    <input type="number" name="duracao_minutos" id="duracao_minutos" value="30" min="15" max="480" required>
                </div>

                <button type="submit" class="btn">Adicionar Tarefa</button>
            </form>

            <div class="stats-info">
                <strong>Total de tarefas hoje:</strong> <?php echo count($tarefas); ?>
            </div>
        </div>

        <!-- Painel de Cronograma -->
        <div class="schedule-panel">
            <div class="date-selector">
                <label>Visualizando:</label>
                <input type="date" id="filterDate" value="<?php echo $data_filtro; ?>" onchange="atualizarData(this.value)">
            </div>

            <div class="schedule-container">
                <?php if (empty($tarefas)): ?>
                    <div class="no-tasks">
                        Nenhuma tarefa agendada para esta data. Comece adicionando uma!
                    </div>
                <?php else: ?>
                    <!-- Extrai os horários únicos e agrupa tarefas -->
                    <?php
                    $horarios_unicos = [];
                    foreach ($tarefas as $tarefa) {
                        $hora_inicio = substr($tarefa['horario_inicio'], 0, 5);
                        if (!in_array($hora_inicio, $horarios_unicos)) {
                            $horarios_unicos[] = $hora_inicio;
                        }
                    }
                    sort($horarios_unicos);
                    ?>

                    <div class="schedule-grid">
                        <?php foreach ($horarios_unicos as $hora): ?>
                            <div class="schedule-hour">
                                <div class="hour-label"><?php echo $hora; ?></div>
                                <?php
                                // Busca tarefas que começam nesta hora
                                $tarefas_hora = array_filter($tarefas, function($t) use ($hora) {
                                    return substr($t['horario_inicio'], 0, 5) === $hora;
                                });
                                
                                foreach ($tarefas_hora as $tarefa):
                                ?>
                                    <div class="task-block">
                                        <div class="task-block-title"><?php echo htmlspecialchars($tarefa['materia']); ?></div>
                                        <div class="task-block-time">
                                            <?php echo substr($tarefa['horario_inicio'], 0, 5); ?> - <?php echo substr($tarefa['horario_fim'], 0, 5); ?>
                                        </div>
                                        <?php if ($tarefa['descricao']): ?>
                                            <div style="margin-top: 5px; font-size: 11px;"><?php echo htmlspecialchars($tarefa['descricao']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>Siga-nos: @ritmoEnem no Instagram e Twitter</p>
</footer>

<script>
function atualizarData(data) {
    window.location.href = 'cronograma.php?data=' + data;
}
</script>

</body>
</html>
