<?php
session_start();
require_once 'conexao.php';
// Função para gerar o cronograma se o formulário for enviado
$scheduleOutput = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $horas = (int)($_POST['horas'] ?? 0); // Horas total por dia
    $horarioInicio = $_POST['horario-inicio'] ?? '';
    $horarioFim = $_POST['horario-fim'] ?? '';
    $dias = $_POST['dias'] ?? [];
    $materiasInput = $_POST['materias'] ?? [];
    $dificuldadesInput = $_POST['dificuldades'] ?? [];

    $errors = [];

    // Validação básica
    if ($horarioInicio >= $horarioFim) {
        $errors[] = 'O horário de início deve ser anterior ao horário de fim.';
    }

    // Processar e validar matérias e dificuldades para garantir que todas tenham dificuldade
    $materiasComDificuldade = [];
    foreach ($materiasInput as $index => $materiaNome) {
        if (!empty(trim($materiaNome))) { // Garante que a matéria não é vazia
            $dificuldadeEncontrada = null;
            // Percorre $dificuldadesInput para encontrar a dificuldade correspondente pelo índice
            // Isso é robusto mesmo que os índices não sejam contíguos devido a remoções no JS
            foreach ($dificuldadesInput as $dificuldadeIndex => $dificuldadeValue) {
                if ($dificuldadeIndex == $index) {
                    $dificuldadeEncontrada = $dificuldadeValue;
                    break;
                }
            }

            if ($dificuldadeEncontrada) {
                 $materiasComDificuldade[] = [
                    'name' => $materiaNome,
                    'level' => $dificuldadeEncontrada
                 ];
            } else {
                 $errors[] = "Por favor, selecione uma dificuldade para a matéria: " . htmlspecialchars($materiaNome);
                 break; // Sai do loop se encontrar um erro para não acumular erros para a mesma matéria
            }
        }
    }

    // Mais validações
    if (empty($materiasComDificuldade) && empty($errors)) {
        $errors[] = 'Por favor, adicione pelo menos uma matéria.';
    }
    if (empty($dias) && empty($errors)) {
        $errors[] = 'Por favor, selecione pelo menos um dia da semana.';
    }
    if ($horas < 2 || $horas > 12 && empty($errors)) {
        $errors[] = 'O número de horas por dia deve ser entre 2 e 12.';
    }

    // Cálculo do intervalo de horas disponíveis como inteiros
    list($startHour, $startMinute) = explode(':', $horarioInicio);
    list($endHour, $endMinute) = explode(':', $horarioFim);
    $startTime = (int)$startHour;
    $endTime = (int)$endHour;

    $totalSlotsAvailableInterval = $endTime - $startTime; // Horas cheias no intervalo

    // Se o intervalo de horas escolhido é menor do que as horas desejadas por dia
    if ($totalSlotsAvailableInterval < $horas && empty($errors)) {
        $errors[] = 'O intervalo de horário selecionado é menor que as horas que você deseja estudar por dia.';
    }


    if (empty($errors)) {
        $schedule = [];
        // Inicializa o cronograma com dias da semana e 24 horas (todos nulos)
        foreach (['seg','ter','qua','qui','sex','sab','dom'] as $dia) {
            $schedule[$dia] = array_fill(0, 24, null);
        }

        // Lógica para calcular 'p' e as horas alocadas por matéria
        $difficultyWeights = ['Fácil' => 1, 'Mediano' => 2, 'Difícil' => 3];
        $totalWeight = 0;
        foreach ($materiasComDificuldade as $m) {
            $totalWeight += $difficultyWeights[$m['level']] ?? 1;
        }

        // Calcula o valor de 'p'. Total de horas de estudo na semana dividido pelo peso total.
        // Isso garante que a proporção de horas por dificuldade seja mantida em relação ao total de horas semanais.
        $totalStudyHoursPerWeek = $horas * count($dias);
        $p = ($totalWeight > 0) ? $totalStudyHoursPerWeek / $totalWeight : 0;

        // Prepara as matérias com suas horas necessárias acumuladas
        $materiasParaAlocar = [];
        foreach ($materiasComDificuldade as $m) {
            $horasNecessarias = ($difficultyWeights[$m['level']] ?? 1) * $p;
            $materiasParaAlocar[] = [
                'name' => $m['name'],
                'level' => $m['level'],
                'horas_restantes' => round($horasNecessarias), // Arredonda para horas cheias para alocação na grade da tabela
                'peso' => $difficultyWeights[$m['level']] ?? 1
            ];
        }

        // Opcional: Ordena as matérias para tentar alocar as mais difíceis primeiro ou de forma consistente
        usort($materiasParaAlocar, function($a, $b) {
            return $b['peso'] <=> $a['peso']; // Ordena do mais difícil para o mais fácil
        });

        $materiasIndex = 0; // Índice para iterar sobre as matérias em $materiasParaAlocar
        $numMaterias = count($materiasParaAlocar);

        // Alocação das matérias no cronograma dia a dia
        foreach ($dias as $dia) {
            $allocatedHoursToday = 0;
            // Loop pelas horas disponíveis no intervalo de estudo para o dia atual
            // Ele vai do horarioInicio até o horarioFim, mas para quando aloca 'horas' por dia
            for ($h = $startTime; $h < $endTime && $allocatedHoursToday < $horas; $h++) {
                if (!empty($materiasParaAlocar)) {
                    // Pega a matéria atual do ciclo
                    $currentMateria = $materiasParaAlocar[$materiasIndex];

                    // Se a matéria atual ainda precisa de horas
                    if ($currentMateria['horas_restantes'] > 0) {
                        // Aloca a matéria no cronograma
                        $schedule[$dia][$h] = [
                            'name' => $currentMateria['name'],
                            'level' => $currentMateria['level']
                        ];
                        // Decrementa as horas restantes da matéria
                        $materiasParaAlocar[$materiasIndex]['horas_restantes']--;
                        $allocatedHoursToday++; // Incrementa as horas alocadas no dia atual
                    } else {
                        // Se a matéria atual já esgotou suas horas, passa para a próxima matéria no array
                        $materiasIndex = ($materiasIndex + 1) % $numMaterias; // Volta ao início se chegou ao fim

                        // Tenta alocar a nova matéria se houver e ela ainda precisar de horas
                        // Se todas as matérias já tiverem suas horas esgotadas, este loop interno pode rodar em falso
                        $startMateriasIndex = $materiasIndex; // Ponto de partida para evitar loop infinito
                        do {
                           if ($materiasParaAlocar[$materiasIndex]['horas_restantes'] > 0) {
                                $currentMateria = $materiasParaAlocar[$materiasIndex];
                                $schedule[$dia][$h] = [
                                    'name' => $currentMateria['name'],
                                    'level' => $currentMateria['level']
                                ];
                                $materiasParaAlocar[$materiasIndex]['horas_restantes']--;
                                $allocatedHoursToday++;
                                break; // Alocou, sai do do-while
                            }
                            $materiasIndex = ($materiasIndex + 1) % $numMaterias; // Tenta a próxima matéria
                        } while ($materiasIndex != $startMateriasIndex); // Continua até encontrar uma matéria com horas ou voltar ao início
                    }
                }
            }
        }


        // Mensagens do dia (ex: Segunda-feira! Vamos começar...)
        $weekMsg = [
            'seg' => 'Segunda-feira! Vamos começar a semana com tudo!',
            'ter' => 'Terça-feira! Continue firme nos estudos!',
            'qua' => 'Quarta-feira! Já estamos no meio da semana!',
            'qui' => 'Quinta-feira! A semana está quase acabando!',
            'sex' => 'Sexta-feira! Dia de revisar tudo que estudou!',
            'sab' => 'Sábado! Dia de descansar e revisar!',
            'dom' => 'Domingo! Dia de descansar e revisar!'
        ];

        // Define a localidade para strftime (necessita da extensão php_intl para funcionar em alguns sistemas)
        // Se ocorrer o erro "IntlDateFormatter not found", esta linha pode estar relacionada ou uma tentativa anterior de usar IntlDateFormatter
        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.iso-8859-1', 'portuguese');
        $hoje = strtolower(substr(strftime('%a', strtotime('today')), 0, 3));
        $mensagem = $weekMsg[$hoje] ?? '';


        // Gera a saída HTML da tabela do cronograma
        $scheduleOutput .= "<div class='message-box'>{$mensagem}</div>";
        $scheduleOutput .= "<h2>Cronograma Gerado: " . htmlspecialchars($nome) . "</h2>";
        $scheduleOutput .= "<p><strong>Horas por dia:</strong> {$horas}</p>";
        $scheduleOutput .= "<p><strong>Horário:</strong> {$horarioInicio} - {$horarioFim}</p>";
        $scheduleOutput .= "<h3>Cronograma</h3>";
        $scheduleOutput .= "<table class='table table-bordered'><thead><tr><th>Horário</th>";
        foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $diaLabel) {
            $scheduleOutput .= "<th>{$diaLabel}</th>";
        }
        $scheduleOutput .= "</tr></thead><tbody>";

        // Loop para gerar as linhas da tabela para cada hora
        for ($h = 0; $h < 24; $h++) {
            // Apenas exibe as linhas da tabela dentro do intervalo de estudo selecionado
            // Isso evita linhas vazias desnecessárias fora do horário de interesse
            if ($h < $startTime || $h >= $endTime) {
                continue;
            }

            $horaStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
            $scheduleOutput .= "<tr><td>{$horaStr}</td>";
            foreach (['seg','ter','qua','qui','sex','sab','dom'] as $d) {
                $s = $schedule[$d][$h] ?? null;
                $corClass = '';
                if ($s) {
                    $corClass = match($s['level']) {
                        'Difícil' => 'difficulty-difficult',
                        'Mediano' => 'difficulty-medium',
                        'Fácil' => 'difficulty-easy',
                        default => ''
                    };
                    $scheduleOutput .= "<td class='{$corClass}'>{$s['name']} - {$s['level']}</td>";
                } else {
                    $scheduleOutput .= "<td></td>"; // Célula vazia se não há matéria alocada
                }
            }
            $scheduleOutput .= "</tr>";
        }
        $scheduleOutput .= "</tbody></table>";
    } else {
        // Exibe erros se houverem validações falhas
        foreach ($errors as $error) {
            $scheduleOutput .= "<div class='alert alert-danger'>{$error}</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cronograma de Estudos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Limelight&family=Kodchasan:wght@400;700&family=Klee+One&family=Linden+Hill&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-beige: #f3f3e0;
            --color-darkcyan: #0097b2;
            --color-linen: #f2efe7;
            --color-darkslateblue: #133e87;
            --color-darkslategray: #16404d;
            --color-black: #000;

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
            background-color: var(--color-beige); /* Fundo padrão das suas páginas */
            color: var(--color-darkslategray);
        }

        /* Estilos do cabeçalho */
        header {
            background-color: var(--color-beige); /* Fundo do cabeçalho igual ao body */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--color-darkslategray);
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header .logo { /* Adicionado para estilizar "Ritmo Enem" no cabeçalho */
        font-size: 24px;
        font-weight: bold;
        color: var(--color-darkcyan); 
        font-family: var(--font-inter); 
    }
        .logo {
            font-family: var(--font-limelight);
            font-size: 36px;
            color: var(--color-darkcyan); /* Cor do logo */
        }

        nav a {
            color: var(--color-darkslategray);
            text-decoration: none;
            margin-left: 25px;
            font-family: var(--font-kodchasan);
            font-weight: bold;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: var(--color-darkcyan);
        }

        .main-page-title {
            font-family: var(--font-limelight);
            font-size: 48px;
            color: var(--color-darkslateblue);
            text-align: center;
            margin-top: 40px;
            margin-bottom: 30px;
        }

        .container {
            padding: 20px;
        }

        h2, h3, h4, h5 {
            font-family: var(--font-limelight);
            color: var(--color-darkslateblue);
        }

        form {
            background-color: var(--color-linen); /* Fundo do formulário */
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: var(--font-kodchasan);
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="time"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
        }

        form button[type="submit"] {
            background-color: var(--color-darkcyan);
            border: none;
            color: white;
            font-size: 18px;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s;
            padding: 12px 30px;
        }

        form button[type="submit"]:hover {
            background-color: #007a91;
        }

        form .form-check-label {
            font-family: var(--font-kodchasan);
            color: var(--color-darkslategray);
        }

        /* Estilos para os botões de dia da semana */
        .day-buttons-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .day-button {
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            border-radius: 15px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
            font-family: var(--font-kodchasan);
            color: var(--color-darkslategray);
        }

        .day-button.selected {
            background-color: #add8e6 !important; /* Azul claro */
            color: white !important;
            border-color: #add8e6 !important;
        }

        .day-button:hover:not(.selected) {
            background-color: #d0d0d0;
        }

        /* Esconde o checkbox original */
        .form-check-input[type="checkbox"] {
            display: none;
        }

        /* Matérias e dificuldade */
        #subjects-container .form-row {
            align-items: center;
            margin-bottom: 10px;
        }

        #subjects-container .form-row input[type="text"] {
            margin-bottom: 0;
        }

        #subjects-container .form-row .col-6 {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        #subjects-container .form-row .col-6 .form-check {
            margin-bottom: 0;
            margin-right: 5px;
        }
        #subjects-container .form-row .col-6 .remove-subject {
            margin-left: 10px;
        }

        #add-subject {
            background-color: var(--color-darkslategray);
            border: none;
            border-radius: 15px;
            padding: 8px 15px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        #add-subject:hover {
            background-color: #2c5a6c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: var(--color-linen); /* Fundo da tabela */
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
            font-family: var(--font-kodchasan);
            color: var(--color-darkslategray);
        }

        table th {
            background-color: var(--color-darkcyan);
            color: white;
            font-weight: bold;
        }

        /* Cores para as células da tabela com base na dificuldade */
        .difficulty-difficult {
            background-color: #dc3545; /* Vermelho */
            color: white;
        }
        .difficulty-medium {
            background-color: #007bff; /* Azul */
            color: white;
        }
        .difficulty-easy {
            background-color: #28a745; /* Verde */
            color: white;
        }

        .message-box {
            background-color: var(--color-darkcyan);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-family: var(--font-kodchasan);
            font-size: 1.1em;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="text-dark"> <header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php">HOME</a>
        <a href="cronograma.php">CRONOGRAMA</a>
        <a href="sobre.php">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
    </nav>
</header>
<h1 class="main-page-title">Cronograma de Estudos</h1>
<main class="container mt-4">
    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <h4>Informações do Cronograma</h4>
                <div class="form-group">
                    <label>Nome do cronograma:</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Horas por dia (2 a 12):</label>
                    <input type="number" name="horas" min="2" max="12" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Horário de Início:</label>
                    <input type="time" name="horario-inicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Horário de Fim:</label>
                    <input type="time" name="horario-fim" class="form-control" required>
                </div>
                <h5>Dias da Semana</h5>
                <div class="day-buttons-container" id="day-buttons">
                    <?php
                    $dias = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
                    foreach ($dias as $id => $label) {
                        // Verifica se o dia foi previamente selecionado (útil para manter seleção após erro de submissão)
                        $checked = in_array($id, $_POST['dias'] ?? []) ? 'checked' : '';
                        $selectedClass = in_array($id, $_POST['dias'] ?? []) ? 'selected' : '';
                        echo "<button type='button' class='day-button {$selectedClass}' data-day-id='{$id}'>{$label}</button>";
                        echo "<input type='checkbox' class='form-check-input day-checkbox' name='dias[]' value='{$id}' id='{$id}' {$checked} style='display:none;'>"; // Checkbox oculto
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Matérias e Dificuldade</h4>
                <div id="subjects-container">
                    <?php
                    // Recarrega as matérias se houver erro de submissão para não perder o que foi digitado
                    if (!empty($materiasInput) && !empty($errors)) {
                        foreach ($materiasInput as $idx => $materiaNomeSaved) {
                            $dificuldadeSaved = '';
                            foreach ($dificuldadesInput as $dificuldadeIdx => $dificuldadeVal) {
                                if ($dificuldadeIdx == $idx) {
                                    $dificuldadeSaved = $dificuldadeVal;
                                    break;
                                }
                            }
                            $isDif = ($dificuldadeSaved == 'Difícil') ? 'checked' : '';
                            $isMed = ($dificuldadeSaved == 'Mediano') ? 'checked' : '';
                            $isFac = ($dificuldadeSaved == 'Fácil') ? 'checked' : '';
                            echo <<<HTML
                            <div class="form-row mb-2">
                                <input type="text" name="materias[]" class="form-control col-6" placeholder="Matéria" value="{$materiaNomeSaved}" required>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="radio" name="dificuldades[{$idx}]" value="Difícil" required class="form-check-input" id="dif-{$idx}" {$isDif}>
                                        <label for="dif-{$idx}" class="form-check-label">Difícil</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="dificuldades[{$idx}]" value="Mediano" required class="form-check-input" id="med-{$idx}" {$isMed}>
                                        <label for="med-{$idx}" class="form-check-label">Mediano</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="dificuldades[{$idx}]" value="Fácil" required class="form-check-input" id="fac-{$idx}" {$isFac}>
                                        <label for="fac-{$idx}" class="form-check-label">Fácil</label>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm remove-subject" style="margin-left: 10px;">X</button>
                                </div>
                            </div>
HTML;
                        }
                    } else { // Se não houve submissão ou não houve erros, exibe o primeiro campo vazio
                    ?>
                    <div class="form-row mb-2">
                        <input type="text" name="materias[]" class="form-control col-6" placeholder="Matéria" required>
                        <div class="col-6">
                            <div class="form-check">
                                <input type="radio" name="dificuldades[0]" value="Difícil" required class="form-check-input" id="dif-0">
                                <label for="dif-0" class="form-check-label">Difícil</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="dificuldades[0]" value="Mediano" required class="form-check-input" id="med-0">
                                <label for="med-0" class="form-check-label">Mediano</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="dificuldades[0]" value="Fácil" required class="form-check-input" id="fac-0">
                                <label for="fac-0" class="form-check-label">Fácil</label>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <button type="button" id="add-subject" class="btn btn-secondary btn-sm">+ Adicionar Matéria</button>
            </div>
        </div>
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success">Gerar Cronograma</button>
        </div>
    </form>

    <div class="mt-5">
        <?= $scheduleOutput ?>
    </div>
</main>

<script>
// Variável global para controlar o índice das novas matérias adicionadas dinamicamente
// Inicializa com o número de matérias existentes se a página foi recarregada com dados POST
let subjectCount = <?php echo count($materiasInput); ?>;

document.getElementById('add-subject').addEventListener('click', function () {
    const container = document.getElementById('subjects-container');
    const div = document.createElement('div');
    div.className = 'form-row mb-2';
    div.innerHTML = `
        <input type="text" name="materias[]" class="form-control col-6" placeholder="Matéria" required>
        <div class="col-6">
            <div class="form-check">
                <input type="radio" name="dificuldades[${subjectCount}]" value="Difícil" required class="form-check-input" id="dif-${subjectCount}">
                <label for="dif-${subjectCount}" class="form-check-label">Difícil</label>
            </div>
            <div class="form-check">
                <input type="radio" name="dificuldades[${subjectCount}]" value="Mediano" required class="form-check-input" id="med-${subjectCount}">
                <label for="med-${subjectCount}" class="form-check-label">Mediano</label>
            </div>
            <div class="form-check">
                <input type="radio" name="dificuldades[${subjectCount}]" value="Fácil" required class="form-check-input" id="fac-${subjectCount}">
                <label for="fac-${subjectCount}" class="form-check-label">Fácil</label>
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-subject" style="margin-left: 10px;">X</button>
        </div>`;
    container.appendChild(div);
    subjectCount++;

    // Adiciona event listener para o novo botão de remover
    div.querySelector('.remove-subject').addEventListener('click', function() {
        div.remove();
        // Chama a função para reindexar os nomes após a remoção
        reindexRadioNames();
    });
});

// Função para reindexar os nomes dos campos de dificuldade
// Essencial para que o PHP receba os dados corretamente após remoções
function reindexRadioNames() {
    const subjectRows = document.querySelectorAll('#subjects-container .form-row');
    subjectRows.forEach((row, index) => {
        row.querySelectorAll('input[type="radio"]').forEach(radio => {
            const oldId = radio.id; // Guarda o ID antigo para atualizar a label
            // O regex extrai o número do índice do nome, ex: dificuldades[0] -> 0
            const oldNameMatch = radio.name.match(/dificuldades\[(\d+)\]/);
            if (oldNameMatch) {
                const oldIndex = oldNameMatch[1];
                radio.name = `dificuldades[${index}]`; // Atualiza o nome para o novo índice
                // Atualiza também os IDs e os atributos 'for' das labels para garantir a acessibilidade
                radio.id = radio.id.replace(`-${oldIndex}`, `-${index}`);
                const label = document.querySelector(`label[for="${oldId}"]`);
                if (label) {
                    label.setAttribute('for', radio.id);
                }
            }
        });
    });
    // Atualiza o subjectCount para o próximo índice correto ao adicionar nova matéria
    subjectCount = subjectRows.length;
}


// Lógica para os botões de seleção de dia (transforma checkboxes em botões clicáveis)
document.addEventListener('DOMContentLoaded', function() {
    const dayButtonsContainer = document.getElementById('day-buttons');
    
    // Delega eventos ao container para lidar com cliques nos botões
    dayButtonsContainer.addEventListener('click', function(event) {
        event.preventDefault(); // Previne o comportamento padrão do botão
        
        // Verifica se o elemento clicado é um botão de dia
        if (event.target.classList.contains('day-button')) {
            const button = event.target;
            const dayId = button.getAttribute('data-day-id');
            const checkbox = document.getElementById(dayId);

            if (checkbox) {
                checkbox.checked = !checkbox.checked; // Inverte o estado do checkbox oculto
                button.classList.toggle('selected', checkbox.checked); // Adiciona/remove a classe 'selected'
            }
        }
    });

    // Ao carregar a página, verifica se algum checkbox já está marcado (ex: após erro de submissão) e atualiza o estado visual dos botões.
    document.querySelectorAll('.day-checkbox').forEach(checkbox => {
        if (checkbox.checked) {
            const button = document.querySelector(`.day-button[data-day-id='${checkbox.id}']`);
            if (button) {
                button.classList.add('selected');
            }
        }
    });
    
    // Adiciona event listeners para os botões de remover matérias que já existem na página (se houver recarregamento com erro)
    document.querySelectorAll('.remove-subject').forEach(button => {
        button.addEventListener('click', function() {
            button.closest('.form-row').remove();
            reindexRadioNames();
        });
    });
});
</script>
</body>
</html>
