<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Busca o usuário no banco
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verifica senha
    if ($user && password_verify($senha, $user['senha'])) {

        // Cria sessão
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];

        // Redireciona
        header("Location: cronograma.php");
        exit();

    } else {
        echo "<script>alert('Email ou senha incorretos.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Login - Ritmo Enem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Kodchasan:wght@400;600&family=Klee+One&family=Limelight&family=Linden+Hill&display=swap" rel="stylesheet">
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
            background-color: var(--color-beige); /* Fundo bege claro */
            color: var(--color-darkslategray); /* Cor padrão do texto */
        }

        header {
            background-color: var(--color-beige); /* Fundo do cabeçalho como o cronograma.php */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--color-darkslategray); /* Cor do texto no cabeçalho */
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Adicionando sombra para consistência */
        }

        header nav a {
            margin: 0 15px;
            color: var(--color-darkslategray); /* Cor dos links do cabeçalho */
            text-decoration: none;
            font-family: var(--font-inter);
            transition: color 0.3s ease; /* Adicionado transição para hover */
        }

        header nav a:hover {
            color: var(--color-darkcyan); /* Cor de acento no hover */
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 60px); /* Ajustado para considerar a altura do header */
            padding: 20px;
        }

        .left-box {
            flex: 1;
            max-width: 500px;
            padding: 20px;
        }

        .left-box h1 {
            font-family: var(--font-limelight);
            font-size: 48px;
            color: var(--color-darkslateblue);
            margin-bottom: 10px;
        }

        .left-box p.tagline {
            font-family: var(--font-linden-hill);
            font-size: 18px;
            color: var(--color-darkslateblue); /* Mantido darkslateblue para tagline */
            margin-bottom: 20px;
        }

        .left-box p.desc {
            font-family: var(--font-klee-one);
            font-size: 18px;
            color: var(--color-darkslategray); /* Alterado para darkslategray para consistência */
            margin-bottom: 40px;
        }

        .socials {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .socials img {
            width: 32px;
            height: 32px;
        }

        .socials span {
            font-size: 14px;
            color: var(--color-darkslategray); /* Alterado para darkslategray para consistência */
        }

        .login-box {
            flex: 1;
            max-width: 450px;
            background-color: var(--color-linen);
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            font-family: var(--font-kodchasan);
        }

        .login-box h2 {
            font-size: 36px;
            color: var(--color-darkslategray);
            margin-bottom: 30px;
        }

        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            font-family: var(--font-inter); /* Usar Inter para inputs */
            color: var(--color-darkslategray); /* Cor do texto dos inputs */
        }

        .login-box input[type="submit"] {
            width: 100%;
            background-color: var(--color-darkcyan);
            border: none;
            color: white;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .login-box input[type="submit"]:hover {
            background-color: #007a91;
        }

        .login-box a {
            display: inline-block;
            margin-top: 15px;
            color: var(--color-darkslategray);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease; /* Adicionado transição para hover */
        }

        .login-box a:hover {
            color: var(--color-darkcyan); /* Cor de acento no hover */
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                padding: 40px 20px;
                height: auto; /* Deixa a altura se ajustar ao conteúdo em telas menores */
            }

            .left-box, .login-box {
                max-width: 100%;
            }

            header {
                flex-direction: column;
                align-items: flex-start; /* Alinha itens à esquerda em telas pequenas */
                gap: 10px;
            }
            header nav { /* Permite que os links da navegação quebrem linha */
                flex-wrap: wrap;
                justify-content: center; /* Centraliza os links quando quebram */
            }
            header nav a {
                margin: 5px 10px; /* Ajusta a margem para links em nav responsivo */
            }
        }
        
    </style>
</head>
<body>

<header>
    <div>Ritmo Enem</div>
    <nav>
        <a href="home.php">HOME</a>
        <a href="cronograma.php">CRONOGRAMA</a> <a href="#">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
        <a href="#">FÓRUM ONLINE</a>
    </nav>
</header>

<div class="container">
    <div class="left-box">
        <h1>Ritmo Enem</h1>
        <p class="tagline">estudando para alcançar as estrelas</p>
        <p class="desc">
            O projeto Ritmo Enem visa facilitar a organização e o acompanhamento de atividades diárias durante a preparação para o ENEM.
            Saiba que pode entrar em contato conosco pelas redes sociais!
        </p>
        <div class="socials">
            <img src="instagram.png" alt="Instagram">
            <span>@stars.__.tech</span>
            <img src="twitter.png" alt="Twitter"> <span>@stars__tech</span>
        </div>
    </div>

    <div class="login-box">
        <h2>LOGIN</h2>
        <form method="POST" action="">
            <input type="text" name="email" placeholder="ENDEREÇO ID" required>
            <input type="password" name="senha" placeholder="SENHA" required>
            <input type="submit" value="LOGIN">
        </form>
        <a href="cadastro.php">Cadastrar-se</a>
    </div>
</div>

</body>
</html>
