<?php
// Dados dinâmicos (pode trocar por variáveis vindas do banco no futuro)
$criadora = "Bárbara Santos Amorim";
$instituicao = "Estudante do IFMG Campus Sabará";
$slogan = "SEJA VOCÊ A SUA MAIOR EXPECTATIVA!";
$missao = "Nossa missão é tornar sua trajetória mais fácil e eficiente, com recursos inovadores e um suporte contínuo para garantir que você alcance seus objetivos acadêmicos.";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - Ritmo Enem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Kodchasan:wght@400;600&family=Klee+One&family=Limelight&family=Linden+Hill&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-beige: #f3f3e0;
            --color-darkcyan: #0097b2;
            --color-linen: #f2efe7;
            --color-darkslateblue: #133e87;
            --color-darkslategray: #16404d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
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
            font-family: 'Limelight', cursive;
            font-size: 32px;
            color: var(--color-darkcyan);
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            color: var(--color-darkslategray);
            text-decoration: none;
            font-family: 'Kodchasan', sans-serif;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        nav a:hover,
        nav a.active {
            color: var(--color-darkcyan);
        }

        .page-header {
            max-width: 1100px;
            margin: 40px auto 0;
            padding: 0 20px;
            text-align: center;
        }

        .page-header h1 {
            font-family: 'Limelight', cursive;
            font-size: 48px;
            color: var(--color-darkslateblue);
            margin-bottom: 10px;
        }

        .page-header p {
            font-family: 'Klee One', cursive;
            font-size: 18px;
            color: var(--color-darkslategray);
            max-width: 760px;
            margin: 0 auto;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .about-card {
            background-color: var(--color-linen);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .about-card img {
            width: 100%;
            max-width: 220px;
            height: auto;
            margin-bottom: 20px;
            border-radius: 20px;
        }

        .about-card h2 {
            font-family: 'Limelight', cursive;
            color: var(--color-darkslateblue);
            margin-bottom: 15px;
        }

        .about-card p {
            font-size: 16px;
            line-height: 1.7;
            color: var(--color-darkslategray);
        }

        .highlight {
            display: inline-block;
            margin-top: 12px;
            font-weight: 700;
            color: var(--color-darkcyan);
        }

        footer {
            background-color: var(--color-darkcyan);
            color: white;
            text-align: center;
            padding: 20px 10px;
            font-family: 'Inter', sans-serif;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 38px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            nav {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php">HOME</a>
        <a href="cronograma.php">CRONOGRAMA</a>
        <a href="sobre.php" class="active">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
    </nav>
</header>

<section class="page-header">
    <h1>Sobre Nós</h1>
    <p>Conheça a proposta do Ritmo Enem, nossa criadora e a missão de ajudar estudantes a seguirem um caminho mais organizado rumo ao ENEM.</p>
</section>

<section class="about-grid">
    <article class="about-card">
        <h2>Criadora</h2>
        <img src="foto-barbara.jpg" alt="Foto da criadora">
        <p><strong><?php echo $criadora; ?></strong><br><?php echo $instituicao; ?></p>
    </article>

    <article class="about-card">
        <h2>Por que surgimos?</h2>
        <img src="laptop-ilustracao.png" alt="Ilustração de laptop">
        <p>O Ritmo Enem nasceu como um trabalho final de curso para oferecer organização, rotina e apoio a estudantes que desejam se preparar com mais foco e confiança.</p>
    </article>

    <article class="about-card">
        <h2>Objetivos</h2>
        <img src="personagem-cartoon.png" alt="Personagem segurando placa">
        <p class="highlight"><?php echo $slogan; ?></p>
        <p><?php echo $missao; ?></p>
    </article>
</section>

<footer>
    <p>Siga-nos: @ritmoEnem no Instagram e Twitter</p>
</footer>
</body>
</html>
