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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .page-header {
            max-width: 1100px;
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

        .page-header p {
            font-family: var(--font-klee-one);
            font-size: 18px;
            color: var(--color-darkslategray);
            max-width: 760px;
            margin: 0 auto;
        }

        .main-content {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px 40px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
            align-items: flex-start;
        }

        .welcome-panel {
            flex: 1;
            min-width: 300px;
            max-width: 520px;
            padding: 40px 0 0 0;
            font-family: var(--font-kodchasan);
        }

        .welcome-panel h1 {
            font-family: var(--font-limelight);
            font-size: 48px;
            color: var(--color-darkslateblue);
            margin-bottom: 20px;
        }

        .welcome-panel p {
            font-family: var(--font-klee-one);
            font-size: 18px;
            color: var(--color-darkslategray);
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .login-card {
            background-color: var(--color-linen);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            font-family: var(--font-kodchasan);
        }

        .login-card {
            background-color: var(--color-linen);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            font-family: var(--font-kodchasan);
        }

        .login-card h2 {
            font-size: 36px;
            color: var(--color-darkslategray);
            margin-bottom: 30px;
            text-align: center;
        }

        .login-card input[type="text"],
        .login-card input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            font-family: var(--font-inter);
            color: var(--color-darkslategray);
        }

        .login-card input[type="submit"] {
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

        .login-card input[type="submit"]:hover {
            background-color: #007a91;
        }

        .login-card .link-row {
            text-align: center;
            margin-top: 20px;
        }

        .login-card a {
            color: var(--color-darkslategray);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-card a:hover {
            color: var(--color-darkcyan);
        }

        footer {
            background-color: var(--color-darkcyan);
            color: white;
            text-align: center;
            padding: 20px 10px;
            font-family: var(--font-inter);
            margin-top: 80px;
        }

        @media (max-width: 900px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            nav {
                justify-content: center;
            }

            .page-header h1 {
                font-size: 38px;
            }

            .main-content {
                flex-direction: column;
                align-items: center;
                padding: 0 20px 30px;
            }

            .welcome-card,
            .login-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php" class="active">HOME</a>
        <a href="cronograma.php">CRONOGRAMA</a>
        <a href="sobre.php">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
        <a href="#">FÓRUM ONLINE</a>
    </nav>
</header>

<main class="main-content">
    <div class="welcome-panel">
        <h1>Bem-vindo ao Ritmo Enem</h1>
        <p>Organize seus estudos de forma prática e intuitiva. Acesse seu cronograma e prepare-se com foco para o ENEM.</p>
        <p>Aqui, você encontra um ambiente leve e funcional para acompanhar seu progresso e manter a rotina de estudos alinhada com seus objetivos.</p>
    </div>

    <div class="login-card">
        <h2>LOGIN</h2>
        <form method="POST" action="">
            <input type="text" name="email" placeholder="ENDEREÇO ID" required>
            <input type="password" name="senha" placeholder="SENHA" required>
            <input type="submit" value="LOGIN">
        </form>
        <div class="link-row">
            <a href="cadastro.php">Cadastrar-se</a>
        </div>
    </div>
</main>

<footer>
    <p>Siga-nos: @ritmoEnem no Instagram e Twitter</p>
</footer>

</body>
</html>
