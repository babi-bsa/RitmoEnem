<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: home.php");
    exit();
}
$usuarioId = (int) $_SESSION['usuario_id'];
function distribuirPorPeso(int $total, array $pesos): array{
    $somaPesos = array_sum($pesos);
    if ($somaPesos <= 0 || $total <= 0) {
        return array_map(fn() => 0, $pesos);
    }

    $base  = [];
    $resto = [];
    $usado = 0;

    foreach ($pesos as $k => $p) {
        $exato    = $total * $p / $somaPesos;
        $base[$k] = (int) floor($exato);
        $resto[$k] = $exato - $base[$k];
        $usado   += $base[$k];
    }

    $sobra = $total - $usado;
    arsort($resto);

    foreach (array_keys($resto) as $k) {
        if ($sobra <= 0) break;
        $base[$k]++;
        $sobra--;
    }

    return $base;
    }
    function blocosDisponiveisNoDia(DateTimeInterface $inicio, DateTimeInterface $fim, int $blocoMin): int
    {
    $minutos = ($fim->getTimestamp() - $inicio->getTimestamp()) / 60;
    return (int) floor($minutos / $blocoMin);
    }
function gerarFilaRevezamento(array $contadores): array
{
    $fila   = [];
    $ultima = null;

    while (array_sum($contadores) > 0) {
        arsort($contadores);
        $escolhida = null;

        foreach ($contadores as $k => $qtd) {
            if ($qtd > 0 && $k !== $ultima) {
                $escolhida = $k;
                break;
            }
        }

        if ($escolhida === null) {
            foreach ($contadores as $k => $qtd) {
                if ($qtd > 0) {
                    $escolhida = $k;
                    break;
                }
            }
        }

        if ($escolhida === null) break;

        $fila[]               = $escolhida;
        $contadores[$escolhida]--;
        $ultima               = $escolhida;
    }

    return $fila;
}
function formatarHora(DateTimeInterface $dt): string
{
    return $dt->format('H:i');
}

function gerarSugestoesHorarios(int $horas, int $blocoMin, array $diasDisponiveis): array
{
    $sugestoes   = [];
    $totalBlocos = (int) round(($horas * 60) / $blocoMin);

    // Sugestão 1 — Aumentar janela diária
    $blocosPor1h = (int) floor(60 / $blocoMin);
    $novasHoras1 = ($totalBlocos * $blocoMin) / 60;
    if ($novasHoras1 <= 10) {
        $sugestoes[] = [
            'titulo'   => '📅 Aumentar janela diária',
            'descricao' => "Estude mais 1 hora por dia. Isso adicionaria ~{$blocosPor1h} bloco(s) ao seu cronograma.",
            'tipo'     => 'horario',
        ];
    }

    // Sugestão 2 — Adicionar mais um dia
    $diasAtuais   = count($diasDisponiveis);
    $diasSugeridos = $diasAtuais + 1;
    if ($diasSugeridos <= 7) {
        $sugestoes[] = [
            'titulo'   => '📆 Adicionar mais um dia',
            'descricao' => "Estudar {$diasSugeridos} dias/semana em vez de {$diasAtuais} dias. "
                         . "Suas horas serão redistribuídas de forma equilibrada.",
            'tipo'     => 'dias',
        ];
    }

    // Sugestão 3 — Blocos mais longos
    $blocoMaior = $blocoMin + 15;
    if ($blocoMaior <= 120) {
        $sugestoes[] = [
            'titulo'   => '⏱️ Blocos mais longos',
            'descricao' => "Use blocos de {$blocoMaior} min em vez de {$blocoMin} min. "
                         . "Menos interrupções, mais foco profundo.",
            'tipo'     => 'bloco',
        ];
    }

    // Sugestão 4 — Reduzir horas
    $horasReduzidas = max(2, $horas - 5);
    $sugestoes[] = [
        'titulo'   => '✨ Estudar menos horas',
        'descricao' => "Considere estudar {$horasReduzidas}h/semana em vez de {$horas}h. "
                     . "Mais qualidade, menos sobrecarga.",
        'tipo'     => 'horas',
    ];

    // Sugestão 5 — Combinação ideal
    $diasIdeal  = 5;
    $horasIdeal = 15;
    $sugestoes[] = [
        'titulo'   => '🎯 Combinação recomendada',
        'descricao' => "A fórmula ideal: {$diasIdeal} dias/semana × {$horasIdeal}h/semana "
                     . "(3h/dia) com blocos de 45–60 min.",
        'tipo'     => 'ideal',
    ];

    return $sugestoes;
}

function salvarCronograma(
    mysqli $conexao,
    int    $usuarioId,
    string $nome,
    int    $horas,
    int    $blocoMin,
    string $horarioInicio,
    string $horarioFim,
    array  $dias,
    array  $prioridades,
    array  $cronograma
): bool {
    $diasJson = json_encode(array_values($dias), JSON_UNESCAPED_UNICODE);

    $prioridadesSanitizadas = [];
    foreach ($dias as $dia) {
        $prioridadesSanitizadas[$dia] = max(1, min(3, (int)($prioridades[$dia] ?? 1)));
    }
    $prioridadesJson = json_encode($prioridadesSanitizadas, JSON_UNESCAPED_UNICODE);

    $stmt = $conexao->prepare(
        "INSERT INTO cronogramas (usuario_id, nome, horas, bloco_min, horario_inicio, horario_fim, dias, prioridades)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) return false;

    $stmt->bind_param('isiissss', $usuarioId, $nome, $horas, $blocoMin, $horarioInicio, $horarioFim, $diasJson, $prioridadesJson);
    if (!$stmt->execute()) { $stmt->close(); return false; }

    $idCronograma = $conexao->insert_id;
    $stmt->close();

    // Prepare a new statement for inserting cronograma items
    $stmt2 = $conexao->prepare(
        "INSERT INTO cronograma_blocos (cronograma_id, dia, inicio, fim, materia, dificuldade)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt2) return false;

    foreach ($cronograma as $dia => $itens) {
        foreach ($itens as $item) {
            $stmt2->bind_param(
                'isisss',
                $idCronograma, $dia,
                $item['inicio'], $item['fim'],
                $item['nome'], $item['nivel']
            );
            if (!$stmt2->execute()) { $stmt2->close(); return false; }
        }
    }

    $stmt2->close();
    return true;
}

$erros            = [];
$saidaCronograma  = '';
$salvoComSucesso  = false;
$mensagemErro     = '';
$sugestoesHorario = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    $nome = trim((string)($_POST['nome']  ?? ''));
    $horas  = (int)($_POST['horas']  ?? 0);
    $blocoMin = (int)($_POST['bloco_min'] ?? 0);
    $horarioInicio = (string) ($_POST['horario_inicio'] ?? '');
    $horarioFim = (string)($_POST['horario_fim'] ?? '');
    $dias = (array)($_POST['dias']?? []);
    $prioridadesIn = (array)($_POST['prioridades']?? []);
    $materiasInput = (array)($_POST['materias']?? []);
    $dificuldadesIn= (array)($_POST['dificuldades']?? []);

    
    if ($nome === '')
        $erros[] = 'Informe um nome para o cronograma.';
    if ($horas < 2 || $horas > 84)
        $erros[] = 'O total de horas semanais deve ser entre 2 e 84.';
    if ($blocoMin < 15 || $blocoMin > 240 || $blocoMin % 15 !== 0)
         $erros[] = 'O tamanho do bloco deve ser entre 15 e 240 min e múltiplo de 15.';
    if (empty($dias))
        $erros[] = 'Selecione pelo menos um dia.';

    $diasValidos = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
    $dias = array_values(array_intersect($diasValidos, $dias));
    if (empty($dias) && !in_array('Selecione pelo menos um dia.', $erros, true)) {
        $erros[] = 'Selecione pelo menos um dia válido.';
    }

    $dtInicio = DateTime::createFromFormat('H:i', $horarioInicio);
    $dtFim = DateTime::createFromFormat('H:i', $horarioFim);
    if (!$dtInicio || !$dtFim) {
        $erros[] = 'Horário inválido.';
    } elseif ($dtInicio >= $dtFim) {
        $erros[] = 'O horário de início deve ser anterior ao horário de fim.';
    }

    // Verifica capacidade da janela de horário
    $blocosPorDiaMax = 0;
    if ($dtInicio && $dtFim && $dtInicio < $dtFim) {
        $blocosPorDiaMax = blocosDisponiveisNoDia($dtInicio, $dtFim, $blocoMin);
        if ($blocosPorDiaMax < 1) {
            $erros[] = "Sua janela de horário não comporta nem 1 bloco de {$blocoMin} min.";
        }
    }

    $totalBlocos = ($blocoMin > 0) ? (int) round(($horas * 60) / $blocoMin) : 0;
    $capacidadeSemanal = $blocosPorDiaMax * count($dias);

    if (empty($erros) && $totalBlocos > $capacidadeSemanal) {
        $horasCap = ($capacidadeSemanal * $blocoMin) / 60;
        $erros[]  = "Com blocos de {$blocoMin} min em " . count($dias)
                  . " dia(s), sua janela suporta no máximo {$horasCap}h/semana. "
                  . "Reduza o total de horas, adicione dias ou amplie o horário.";

        // Gera sugestões quando a capacidade é excedida
        $sugestoesHorario = gerarSugestoesHorarios($horas, $blocoMin, $dias);
    }

    // Monta a lista de matérias validando a dificuldade
    $materias = [];
    foreach ($materiasInput as $i => $m) {
        $m = trim((string)$m);
        if ($m === '') continue;

        $dif = $dificuldadesIn[$i] ?? null;
        if (!in_array($dif, ['Fácil', 'Médio', 'Difícil'], true)) {
            $erros[] = 'Selecione uma dificuldade válida para: ' . htmlspecialchars($m);
            continue;
        }
        $materias[] = ['nome' => $m, 'nivel' => $dif];
    }
    if (empty($materias) && !array_filter($erros, fn($e) => str_contains($e, 'dificuldade'))) {
        $erros[] = 'Adicione pelo menos uma matéria.';
    }

    /* ── AQUI É O TCHAN: GERA O CRONOGRAMA ── */
    if (empty($erros)) {

        // 1) Distribuição de blocos por matéria (proporcional à dificuldade)
        $pesoDificuldade = ['Fácil' => 1, 'Médio' => 2, 'Difícil' => 3];
        $pesoMateria = [];
        foreach ($materias as $k => $m) {
            $pesoMateria[$k] = $pesoDificuldade[$m['nivel']];
        }
        $blocosPorMateria = distribuirPorPeso($totalBlocos, $pesoMateria);

        // 2) Distribuição de blocos por dia (proporcional à prioridade)
        $pesoDia = [];
        foreach ($dias as $d) {
            $p = (int)($prioridadesIn[$d] ?? 1);
            $pesoDia[$d] = max(1, min(3, $p));
        }
        $blocosPorDia = distribuirPorPeso($totalBlocos, $pesoDia);

        // Corrige overflow: realoca excedente para dias com folga
        $excedente = 0;
        foreach ($blocosPorDia as $d => $qtd) {
            if ($qtd > $blocosPorDiaMax) {
                $excedente       += $qtd - $blocosPorDiaMax;
                $blocosPorDia[$d] = $blocosPorDiaMax;
            }
        }
        while ($excedente > 0) {
            $colocou = false;
            arsort($pesoDia);
            foreach (array_keys($pesoDia) as $d) {
                if ($blocosPorDia[$d] < $blocosPorDiaMax) {
                    $blocosPorDia[$d]++;
                    $excedente--;
                    $colocou = true;
                    if ($excedente === 0) break;
                }
            }
            if (!$colocou) break; // não há mais espaço disponível
        }

        // 3) Fila global de matérias em revezamento (sem repetição consecutiva)
        $fila = gerarFilaRevezamento($blocosPorMateria);

        // 4) Alocação da fila nos slots de cada dia
        $cronograma = [];
        foreach ($diasValidos as $d) $cronograma[$d] = [];

        $indiceFila = 0;
        foreach ($dias as $d) {
            $limite = $blocosPorDia[$d];
            $cursor = clone $dtInicio;

            for ($b = 0; $b < $limite && $indiceFila < count($fila); $b++) {
                $indiceMateria = $fila[$indiceFila++];
                $horaInicio    = clone $cursor;
                $cursor->modify("+{$blocoMin} minutes");

                $cronograma[$d][] = [
                    'inicio' => formatarHora($horaInicio),
                    'fim'    => formatarHora($cursor),
                    'nome'   => $materias[$indiceMateria]['nome'],
                    'nivel'  => $materias[$indiceMateria]['nivel'],
                ];
            }
        }

        // Salva no banco de dados
        require_once __DIR__ . '/conexao.php';
        $salvoComSucesso = salvarCronograma(
            $conn, 
            $usuarioId,
            $nome,
            $horas,
            $blocoMin,
            $horarioInicio,
            $horarioFim,
            $dias,
            $prioridadesIn,
            $cronograma
            );
            if (!$salvoComSucesso) {
                $mensagemErro = 'Não foi possível salvar o cronograma no banco de dados.';
                }
        // Mensagem motivacional do dia
        $mensagemDia = [
            'seg' => 'Segunda-feira: Vamos começar a semana com tudo!',
            'ter' => 'Terça-feira: Continue firme nos estudos!',
            'qua' => 'Quarta-feira: Já estamos no meio da semana!',
            'qui' => 'Quinta-feira: A semana está quase acabando!',
            'sex' => 'Sexta-feira: Dia de revisar tudo que estudou!',
            'sab' => 'Sábado: Ótimo dia para sessões mais longas!',
            'dom' => 'Domingo: Revisão e descanso!',
        ];
        $hoje      = $diasValidos[(int)(new DateTime())->format('N') - 1];
        $motivacao = $mensagemDia[$hoje] ?? '';

        // Gera linhas de horário da tabela
        $linhas = [];
        $cursor = clone $dtInicio;
        while ($cursor < $dtFim) {
            $horaInicio = clone $cursor;
            $cursor->modify("+{$blocoMin} minutes");
            $linhas[] = ['inicio' => formatarHora($horaInicio), 'fim' => formatarHora($cursor)];
        }

        // Indexa blocos por "dia|horario" para lookup rápido
        $porChave = [];
        foreach ($cronograma as $d => $itens) {
            foreach ($itens as $item) {
                $porChave[$d . '|' . $item['inicio']] = $item;
            }
        }

        $labelDias = ['seg' => 'Seg', 'ter' => 'Ter', 'qua' => 'Qua', 'qui' => 'Qui',
                      'sex' => 'Sex', 'sab' => 'Sáb', 'dom' => 'Dom'];

        // Monta HTML da tabela
        $saidaCronograma  = "<p class='semana-msg'>" . htmlspecialchars($motivacao) . "</p>";



        $saidaCronograma .= "<h2>Cronograma: " . htmlspecialchars($nome) . "</h2>";
        $saidaCronograma .= "<p>Total semanal: {$horas}h &nbsp;|&nbsp; Bloco: {$blocoMin} min"
                          . " &nbsp;|&nbsp; Horário: "
                          . htmlspecialchars($horarioInicio) . " – " . htmlspecialchars($horarioFim) . "</p>";

        $saidaCronograma .= "<table class='tabela-cronograma'><thead><tr><th>Horário</th>";
        foreach ($dias as $d) {
            $saidaCronograma .= "<th>" . $labelDias[$d] . "</th>";
        }
        $saidaCronograma .= "</tr></thead><tbody>";

        foreach ($linhas as $linha) {
            $saidaCronograma .= "<tr><th>{$linha['inicio']}–{$linha['fim']}</th>";

            foreach ($dias as $d) {
                $chave   = $d . '|' . $linha['inicio'];
                $idCelula = $d . '_' . str_replace(':', '', $linha['inicio']);

                if (isset($porChave[$chave])) {
                    $slot   = $porChave[$chave];
                    $classe = match ($slot['nivel']) {
                        'Difícil' => 'dificuldade-dificil',
                        'Médio'   => 'dificuldade-medio',
                        'Fácil'   => 'dificuldade-facil',
                        default   => '',
                    };
                    $saidaCronograma .= "<td
                        class='celula-cronograma {$classe}'
                        data-id-celula='{$idCelula}'
                        data-dia='{$d}'
                        data-horario='{$linha['inicio']}'
                        draggable='true'>";
                    $saidaCronograma .= "<div class='acoes-celula'>";
                    $saidaCronograma .= "<button class='btn-acao btn-editar' title='Editar bloco'>✏️</button>";
                    $saidaCronograma .= "<button class='btn-acao btn-apagar' title='Apagar bloco'>✕</button>";
                    $saidaCronograma .= "</div>";
                    $saidaCronograma .= "<div class='conteudo-celula'>"
                                      . htmlspecialchars($slot['nome'])
                                      . "</div>";
                    $saidaCronograma .= "</td>";
                } else {
                    $saidaCronograma .= "<td
                        class='vazia celula-cronograma'
                        data-id-celula='{$idCelula}'
                        data-dia='{$d}'
                        data-horario='{$linha['inicio']}'
                        draggable='true'>";
                    $saidaCronograma .= "<div class='acoes-celula'>";
                    $saidaCronograma .= "<button class='btn-acao btn-editar' title='Editar bloco'>✏️</button>";
                    $saidaCronograma .= "<button class='btn-acao btn-apagar' title='Apagar bloco'>✕</button>";
                    $saidaCronograma .= "</div>";
                    $saidaCronograma .= "<div class='conteudo-celula'></div>";
                    $saidaCronograma .= "</td>";
                }
            }
            $saidaCronograma .= "</tr>";
        }

        $saidaCronograma .= "</tbody></table>";

    } else {
        // Exibe erros de validação
        foreach ($erros as $e) {
            $saidaCronograma .= "<p class='erro'>" . htmlspecialchars($e) . "</p>";
        }

        // Exibe sugestões quando a capacidade for excedida
        if (!empty($sugestoesHorario)) {
            $saidaCronograma .= "<div class='container-sugestoes'>";
            $saidaCronograma .= "<h3 class='titulo-sugestoes'>💡 Sugestões para o seu Cronograma</h3>";
            foreach ($sugestoesHorario as $sug) {
                $saidaCronograma .= "<div class='card-sugestao'>";
                $saidaCronograma .= "<h4>" . htmlspecialchars($sug['titulo']) . "</h4>";
                $saidaCronograma .= "<p>"  . htmlspecialchars($sug['descricao']) . "</p>";
                $saidaCronograma .= "</div>";
            }
            $saidaCronograma .= "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cronograma de Estudos – Ritmo Enem</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Limelight&family=Kodchasan:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cor-bege:           #f3f3e0;
            --cor-ciano:          #0097b2;
            --cor-linho:          #f2efe7;
            --cor-azul-escuro:    #133e87;
            --cor-verde-escuro:   #16404d;
            --fonte-inter:        'Inter', sans-serif;
            --fonte-limelight:    'Limelight', cursive;
            --fonte-kodchasan:    'Kodchasan', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--fonte-inter);
            background-color: var(--cor-bege);
            color: var(--cor-verde-escuro);
        }

        header {
            background-color: var(--cor-bege);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo {
            font-family: var(--fonte-limelight);
            font-size: 32px;
            color: var(--cor-ciano);
        }

        nav a {
            color: var(--cor-verde-escuro);
            text-decoration: none;
            margin-left: 25px;
            font-family: var(--fonte-kodchasan);
            font-weight: bold;
            transition: color 0.3s;
        }
        nav a:hover { color: var(--cor-ciano); }

        .titulo-pagina {
            font-family: var(--fonte-limelight);
            font-size: 42px;
            color: var(--cor-azul-escuro);
            text-align: center;
            margin: 36px 0 28px;
        }

        .container { padding: 20px; }

        form {
            background-color: var(--cor-linho);
            padding: 36px;
            border-radius: 24px;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
            font-family: var(--fonte-kodchasan);
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="time"],
        select {
            width: 100%;
            padding: 10px 14px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 15px;
            font-family: var(--fonte-kodchasan);
            background: #fff;
        }
        form input:focus {
            outline: none;
            border-color: var(--cor-ciano);
            box-shadow: 0 0 0 3px rgba(0,151,178,0.15);
        }

        /* Botões de dias da semana */
        .container-dias {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 12px 0 20px;
        }
        .botao-dia {
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 7px 14px;
            cursor: pointer;
            font-family: var(--fonte-kodchasan);
            color: var(--cor-verde-escuro);
            font-size: 14px;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        }
        .botao-dia.selecionado {
            background-color: var(--cor-ciano) !important;
            color: #fff !important;
            border-color: var(--cor-ciano) !important;
        }
        .botao-dia:hover:not(.selecionado) { background-color: #d0d0d0; }

        
        .linha-materia {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }
        .opcoes-dificuldade {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        #adicionar-materia {
            background-color: var(--cor-verde-escuro);
            border: none;
            border-radius: 20px;
            padding: 8px 18px;
            color: #fff;
            cursor: pointer;
            font-family: var(--fonte-kodchasan);
            font-size: 14px;
            transition: background-color 0.2s;
            margin-top: 4px;
        }
        #adicionar-materia:hover { background-color: #2c5a6c; }

        form button[type="submit"] {
            background-color: var(--cor-ciano);
            border: none;
            color: #fff;
            font-size: 17px;
            font-weight: bold;
            font-family: var(--fonte-kodchasan);
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s;
            padding: 12px 36px;
        }
        form button[type="submit"]:hover { background-color: #007a91; }

        
        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: var(--cor-linho);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
            font-family: var(--fonte-kodchasan);
            color: var(--cor-verde-escuro);
            font-size: 14px;
        }
        table th {
            background-color: var(--cor-ciano);
            color: #fff;
            font-weight: bold;
        }

        
        .dificuldade-dificil { background-color: #dc3545; color: #fff; }
        .dificuldade-medio   { background-color: #007bff; color: #fff; }
        .dificuldade-facil   { background-color: #28a745; color: #fff; }

        
        .celula-cronograma {
            min-width: 120px;
            min-height: 60px;
            cursor: grab;
            transition: opacity 0.2s, background 0.15s;
            vertical-align: middle;
            position: relative;
            user-select: none;
        }
        
        .celula-cronograma.editando {
            cursor: text;
            outline: 3px solid var(--cor-ciano) !important;
            background: #fff !important;
            color: #222 !important;
        }
        .arrastando  { opacity: 0.35; cursor: grabbing; }
        .sobre-alvo  { border: 3px dashed var(--cor-azul-escuro) !important; background: rgba(19,62,135,0.07) !important; }

        
        .conteudo-celula {
            min-height: 24px;
            outline: none;
            font-family: var(--fonte-kodchasan);
            font-size: 13px;
            word-break: break-word;
            padding: 2px 4px;
        }

        
        .acoes-celula {
            display: flex;
            gap: 4px;
            position: absolute;
            top: 4px;
            right: 4px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .15s ease, visibility .15s ease;
        }
        .celula-cronograma:hover .acoes-celula {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .btn-acao {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 3px 8px rgba(0,0,0,0.18);
            transition: transform .1s ease, box-shadow .1s ease;
        }
        .btn-acao:hover { transform: scale(1.12); box-shadow: 0 6px 14px rgba(0,0,0,0.22); }
        .btn-apagar  { background: linear-gradient(135deg,#ff6b6b,#ff4757); color:#fff; }
        .btn-editar  { background: linear-gradient(135deg,#f8c200,#f39c12); color:#fff; }

        
        .celula-cronograma.vazia .conteudo-celula::after {
            content: 'Arraste ou clique ✂️';
            font-size: 10px;
            color: #aaa;
            display: block;
        }

        @media (max-width: 480px) {
            .acoes-celula { opacity: 1; visibility: visible; pointer-events: auto; }
        }

        /* Mensagens de retorno */
        .semana-msg {
            background-color: var(--cor-ciano);
            color: #fff;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-family: var(--fonte-kodchasan);
            font-size: 1.05em;
        }
        
        .erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-family: var(--fonte-kodchasan);
        }

        /* Sugestões de horário */
        .container-sugestoes {
            margin-top: 24px;
            padding: 20px;
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cc 100%);
            border-left: 5px solid #ffc107;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(255,193,7,0.2);
        }
        .titulo-sugestoes {
            color: #ff8c00;
            font-family: var(--fonte-kodchasan);
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 16px;
            text-align: center;
        }
        .card-sugestao {
            background-color: #fff;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .card-sugestao:hover {
            box-shadow: 0 6px 16px rgba(255,193,7,0.3);
            transform: translateY(-2px);
        }
        .card-sugestao h4 {
            color: var(--cor-ciano);
            font-family: var(--fonte-kodchasan);
            font-size: 1.05em;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .card-sugestao p {
            color: #444;
            font-family: var(--fonte-kodchasan);
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php">INÍCIO</a>
        <a href="cronograma.php">CRONOGRAMA</a>
        <a href="sobre.php">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
        <a href="logout.php">SAIR</a>
    </nav>
</header>

<h1 class="titulo-pagina">Cronograma de Estudos</h1>

<main class="container mt-2">
    <form method="POST">
        <div class="row">

            <!-- Coluna Esquerda: configurações gerais -->
            <div class="col-md-6">
                <h4>Informações do Cronograma</h4><br>

                <div class="form-group">
                    <label>Nome do cronograma:</label>
                    <input type="text" name="nome" class="form-control"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Total de horas de estudo na semana (2 a 84):</label>
                    <input type="number" name="horas" min="2" max="84" class="form-control"
                           value="<?= htmlspecialchars($_POST['horas'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Tamanho do bloco (minutos):</label>
                    <select name="bloco_min" class="form-control">
                        <option value="" disabled <?= !isset($_POST['bloco_min']) ? 'selected' : '' ?>>Selecione...</option>
                        <?php foreach ([30, 45, 60, 90, 120] as $opcao):
                            $selecionado = isset($_POST['bloco_min']) && (int)$_POST['bloco_min'] === $opcao ? 'selected' : ''; ?>
                            <option value="<?= $opcao ?>" <?= $selecionado ?>><?= $opcao ?> min</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Horário de início:</label>
                    <input type="time" name="horario_inicio" class="form-control"
                           value="<?= htmlspecialchars($_POST['horario_inicio'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Horário de fim:</label>
                    <input type="time" name="horario_fim" class="form-control"
                           value="<?= htmlspecialchars($_POST['horario_fim'] ?? '') ?>" required>
                </div>

                <h5>Dias da Semana</h5>
                <div class="container-dias" id="botoes-dias">
                    <?php
                    $rotulosDias = [
                        'seg' => 'Segunda', 'ter' => 'Terça',  'qua' => 'Quarta',
                        'qui' => 'Quinta',  'sex' => 'Sexta',  'sab' => 'Sábado', 'dom' => 'Domingo',
                    ];
                    foreach ($rotulosDias as $id => $rotulo):
                        $marcado       = in_array($id, $_POST['dias'] ?? []) ? 'checked' : '';
                        $classeSelecionada = in_array($id, $_POST['dias'] ?? []) ? 'selecionado' : '';
                    ?>
                        <button type="button"
                                class="botao-dia <?= $classeSelecionada ?>"
                                data-id-dia="<?= $id ?>"><?= $rotulo ?></button>
                        <input type="checkbox"
                               class="form-check-input checkbox-dia"
                               name="dias[]" value="<?= $id ?>"
                               id="dia-<?= $id ?>" <?= $marcado ?>
                               style="display:none;">
                    <?php endforeach; ?>
                </div>

                <div class="form-group mt-2">
                    <label>Prioridade por dia (1 – 3)</label>
                    <div class="d-flex flex-wrap" style="gap:8px;">
                        <?php foreach ($rotulosDias as $id => $rotulo):
                            $prioridade = isset($_POST['prioridades'][$id]) ? (int)$_POST['prioridades'][$id] : 0; ?>
                            <div style="margin-right:8px; min-width:110px;">
                                <small><?= $rotulo ?></small>
                                <select name="prioridades[<?= $id ?>]" class="form-control">
                                    <option value="0" <?= $prioridade === 0 ? 'selected' : '' ?>>—</option>
                                    <?php foreach ([1, 2, 3] as $p): ?>
                                        <option value="<?= $p ?>" <?= $prioridade === $p ? 'selected' : '' ?>>P<?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: matérias e dificuldades -->
            <div class="col-md-6">
                <h4>Matérias e Dificuldade</h4><br>

                <div id="container-materias">
                    <?php
                    $listaMaterias     = $_POST['materias']     ?? [''];
                    $listaDificuldades = $_POST['dificuldades'] ?? [null];

                    foreach ($listaMaterias as $i => $m):
                        $m   = htmlspecialchars((string)$m);
                        $dif = $listaDificuldades[$i] ?? null;
                        $ehDificil = ($dif === 'Difícil') ? 'checked' : '';
                        $ehMedio   = ($dif === 'Médio')   ? 'checked' : '';
                        $ehFacil   = ($dif === 'Fácil')   ? 'checked' : '';
                    ?>
                        <div class="linha-materia">
                            <input type="text" name="materias[]" class="form-control"
                                   placeholder="Nome da matéria" value="<?= $m ?>">
                            <div class="opcoes-dificuldade">
                                <div class="form-check">
                                    <input type="radio" name="dificuldades[<?= $i ?>]"
                                           value="Difícil" class="form-check-input"
                                           id="dificil-<?= $i ?>" <?= $ehDificil ?>>
                                    <label for="dificil-<?= $i ?>" class="form-check-label">Difícil</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="dificuldades[<?= $i ?>]"
                                           value="Médio" class="form-check-input"
                                           id="medio-<?= $i ?>" <?= $ehMedio ?>>
                                    <label for="medio-<?= $i ?>" class="form-check-label">Médio</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="dificuldades[<?= $i ?>]"
                                           value="Fácil" class="form-check-input"
                                           id="facil-<?= $i ?>" <?= $ehFacil ?>>
                                    <label for="facil-<?= $i ?>" class="form-check-label">Fácil</label>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remover-materia">Remover</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="adicionar-materia">+ Adicionar Matéria</button>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit">Gerar Cronograma</button>
        </div>
    </form>

    <div class="mt-5 table-responsive">
        <?= $saidaCronograma ?>
    </div>
</main>

<script>
let contadorMaterias = <?= max(1, count($_POST['materias'] ?? [])) ?>;

/** Reindexar os nomes dos radios após remoção de uma linha */
function reindexarRadios() {
    const linhas = document.querySelectorAll('#container-materias .linha-materia');
    linhas.forEach((linha, indice) => {
        linha.querySelectorAll('input[type="radio"]').forEach(radio => {
            const idAntigo = radio.id;
            const prefixo  = idAntigo.replace(/-\d+$/, '');
            const idNovo   = `${prefixo}-${indice}`;

            const rotulo = linha.querySelector(`label[for="${idAntigo}"]`);
            if (rotulo) rotulo.setAttribute('for', idNovo);

            radio.id   = idNovo;
            radio.name = `dificuldades[${indice}]`;
        });
    });
    contadorMaterias = linhas.length;
}


function criarLinhaMateria(idx) {
    const div = document.createElement('div');
    div.className = 'linha-materia';
    div.innerHTML = `
        <input type="text" name="materias[]" class="form-control" placeholder="Nome da matéria">
        <div class="opcoes-dificuldade">
            <div class="form-check">
                <input type="radio" name="dificuldades[${idx}]" value="Difícil"
                       class="form-check-input" id="dificil-${idx}">
                <label for="dificil-${idx}" class="form-check-label">Difícil</label>
            </div>
            <div class="form-check">
                <input type="radio" name="dificuldades[${idx}]" value="Médio"
                       class="form-check-input" id="medio-${idx}" checked>
                <label for="medio-${idx}" class="form-check-label">Médio</label>
            </div>
            <div class="form-check">
                <input type="radio" name="dificuldades[${idx}]" value="Fácil"
                       class="form-check-input" id="facil-${idx}">
                <label for="facil-${idx}" class="form-check-label">Fácil</label>
            </div>
        </div>`;

    const btnRemover = document.createElement('button');
    btnRemover.type        = 'button';
    btnRemover.className   = 'btn btn-danger btn-sm remover-materia';
    btnRemover.textContent = 'Remover';
    btnRemover.addEventListener('click', () => { div.remove(); reindexarRadios(); });

    div.querySelector('.opcoes-dificuldade').appendChild(btnRemover);
    return div;
}

/** Inicializa todos os componentes interativos da página */
function iniciarInterface() {
    // Botão "Adicionar Matéria"
    const btnAdicionar = document.getElementById('adicionar-materia');
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', () => {
            const container = document.getElementById('container-materias');
            if (container) {
                container.appendChild(criarLinhaMateria(contadorMaterias));
                contadorMaterias++;
            }
        });
    }

    // Botões "Remover" das matérias já renderizadas
    document.querySelectorAll('.remover-materia').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.linha-materia')?.remove();
            reindexarRadios();
        });
    });

    // Seleção de dias da semana via botões
    const containerDias = document.getElementById('botoes-dias');
    if (containerDias) {
        containerDias.addEventListener('click', (evento) => {
            const botao = evento.target.closest('.botao-dia');
            if (!botao) return;
            evento.preventDefault();

            const idDia   = botao.getAttribute('data-id-dia');
            const checkbox = document.getElementById('dia-' + idDia);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                botao.classList.toggle('selecionado', checkbox.checked);
            }
        });
    }

    
    document.querySelectorAll('.checkbox-dia').forEach(checkbox => {
        if (checkbox.checked) {
            const idDia = checkbox.id.replace('dia-', '');
            const btn   = document.querySelector(`.botao-dia[data-id-dia='${idDia}']`);
            if (btn) btn.classList.add('selecionado');
        }
    });

    
    iniciarArrastarSoltar();
}

if (document.readyState !== 'loading') {
    iniciarInterface();
} else {
    document.addEventListener('DOMContentLoaded', iniciarInterface);
}

/*-Arrastar e soltar celula-*/
let celulaArrastada = null;

function iniciarArrastarSoltar() {
    document.querySelectorAll('.celula-cronograma').forEach(celula => {

        celula.addEventListener('dragstart', (e) => {
            // Não arrasta enquanto está editando
            if (celula.classList.contains('editando')) { e.preventDefault(); return; }
            celulaArrastada = celula;
            celula.classList.add('arrastando');
        });

        celula.addEventListener('dragend', () => {
            celula.classList.remove('arrastando');
            celulaArrastada = null;
        });

        celula.addEventListener('dragover', e => {
            e.preventDefault();
            if (celulaArrastada && celulaArrastada !== celula)
                celula.classList.add('sobre-alvo');
        });

        celula.addEventListener('dragleave', () => celula.classList.remove('sobre-alvo'));

        celula.addEventListener('drop', e => {
            e.preventDefault();
            celula.classList.remove('sobre-alvo');
            if (!celulaArrastada || celulaArrastada === celula) return;

            // Guarda conteúdo e cor de cada célula
            const classesDificuldade = ['dificuldade-dificil', 'dificuldade-medio', 'dificuldade-facil'];
            const obterDif = el => classesDificuldade.find(c => el.classList.contains(c)) || null;

            const conteudoAlvo      = celula.querySelector('.conteudo-celula').innerHTML;
            const conteudoArrastado = celulaArrastada.querySelector('.conteudo-celula').innerHTML;
            const difAlvo           = obterDif(celula);
            const difArrastada      = obterDif(celulaArrastada);
            const vaziaAlvo         = celula.classList.contains('vazia');
            const vaziaArrastada    = celulaArrastada.classList.contains('vazia');

            // Troca conteúdo
            celula.querySelector('.conteudo-celula').innerHTML      = conteudoArrastado;
            celulaArrastada.querySelector('.conteudo-celula').innerHTML = conteudoAlvo;

            // Troca classes de dificuldade e vazia
            classesDificuldade.forEach(c => { celula.classList.remove(c); celulaArrastada.classList.remove(c); });
            celula.classList.toggle('vazia', vaziaArrastada);
            celulaArrastada.classList.toggle('vazia', vaziaAlvo);
            if (difArrastada) celula.classList.add(difArrastada);
            if (difAlvo)      celulaArrastada.classList.add(difAlvo);
        });
    });
}

/*-Apagar ou editar celula-*/
document.addEventListener('click', (e) => {

    /* ─ Apagar bloco ─ */
    if (e.target.closest('.btn-apagar')) {
        e.preventDefault();
        const celula = e.target.closest('.celula-cronograma');
        if (!celula) return;

        if (!window.confirm('Apagar este bloco de estudo?')) return;

        celula.querySelector('.conteudo-celula').innerHTML = '';
        ['dificuldade-dificil', 'dificuldade-medio', 'dificuldade-facil'].forEach(c => celula.classList.remove(c));
        celula.classList.add('vazia');
        celula.classList.remove('editando');
        celula.removeAttribute('draggable');
        celula.setAttribute('draggable', 'true');
        return;
    }

    /* ─ Editar bloco ─ */
    if (e.target.closest('.btn-editar')) {
        e.preventDefault();
        const celula = e.target.closest('.celula-cronograma');
        if (!celula) return;

        const conteudo = celula.querySelector('.conteudo-celula');
        celula.classList.add('editando');
        celula.setAttribute('draggable', 'false'); // desativa arrastar durante edição
        conteudo.setAttribute('contenteditable', 'true');
        conteudo.focus();

        // Posiciona cursor no fim do texto
        const sel = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(conteudo);
        range.collapse(false);
        sel.removeAllRanges();
        sel.addRange(range);

        // Salva ao pressionar Enter ou ao perder o foco
        const salvarEdicao = () => {
            conteudo.removeAttribute('contenteditable');
            celula.classList.remove('editando');
            celula.setAttribute('draggable', 'true');
            if (conteudo.textContent.trim() === '') {
                celula.classList.add('vazia');
            } else {
                celula.classList.remove('vazia');
            }
            conteudo.removeEventListener('blur',    salvarEdicao);
            conteudo.removeEventListener('keydown', aoTeclar);
        };
        const aoTeclar = (ev) => {
            if (ev.key === 'Enter')  { ev.preventDefault(); salvarEdicao(); }
            if (ev.key === 'Escape') { conteudo.textContent = conteudo.dataset.original || ''; salvarEdicao(); }
        };

        // Guarda valor original para cancelar com Escape
        conteudo.dataset.original = conteudo.textContent;
        conteudo.addEventListener('blur',    salvarEdicao,  { once: true });
        conteudo.addEventListener('keydown', aoTeclar);
        return;
    }
});
</script>
</body>
</html>