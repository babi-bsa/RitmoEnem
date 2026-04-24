<?php
session_start();
require_once 'conexao.php';
// Processa o formulário
$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $nascimento = $_POST['nascimento'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $estilos = isset($_POST['estilo']) ? implode(", ", $_POST['estilo']) : '';

    $foto = '';
    $mensagem = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nomeFoto = uniqid() . "-" . $_FILES['foto']['name'];
        // Certifique-se de que a pasta 'imagens' existe no mesmo diretório deste script.
        // O caminho correto deve ser relativo ao script PHP.
        $diretorioImagens = __DIR__ . "/imagens/";
        if (!is_dir($diretorioImagens)) {
            mkdir($diretorioImagens, 0777, true); // Cria o diretório se não existir
        }
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $diretorioImagens . $nomeFoto)) {
            $foto = "imagens/" . $nomeFoto; // Armazena o caminho relativo para uso no HTML
        } else {
            $mensagem = "Erro ao mover o arquivo da foto.";
        }
    }

    if (!$mensagem) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $email, $senhaHash);

        if ($stmt->execute()) {
            $mensagem = "Cadastro realizado com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastro - Ritmo Enem</title>
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

    /* Reset básico */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    
    body {
        font-family: var(--font-inter);
        background-color: var(--color-beige); 
        color: var(--color-darkslategray); 
        display: flex; 
        flex-direction: column;
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

    header .logo { /* Adicionado para estilizar "Ritmo Enem" no cabeçalho */
        font-size: 24px;
        font-weight: bold;
        color: var(--color-darkcyan); 
        font-family: var(--font-inter); 
    }

    header nav {
        display: flex;
    }

    header nav a {
        margin: 0 15px;
        color: var(--color-darkslategray); 
        text-decoration: none;
        font-family: var(--font-inter);
        transition: color 0.3s ease;
    }

    header nav a:hover {
        color: var(--color-darkcyan); /* Hover nos links */
    }

    /* Título principal da página */
    .main-page-title {
        font-family: var(--font-limelight);
        font-size: 48px;
        color: var(--color-darkslateblue);
        text-align: center;
        margin-top: 40px;
        margin-bottom: 30px;
    }

    /* Container principal do formulário (form-box) */
    .form-box {
        max-width: 600px; 
        margin: auto; 
        background-color: var(--color-linen); 
        padding: 40px; 
        border-radius: 30px; 
        box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        font-family: var(--font-kodchasan); 
        position: relative;
        padding-top: 60px; 
        padding-right: 40px; 
    }

    /* Título do formulário */
    h2 {
        color: var(--color-darkslategray); 
        text-align: center;
        font-family: var(--font-kodchasan);
        margin-top: 0;
        margin-bottom: 25px; 
        font-size: 36px; 
    }

    /* Labels */
    label {
        font-weight: bold;
        display: block;
        margin-top: 20px; 
        margin-bottom: 8px; 
        font-family: var(--font-kodchasan); 
        color: var(--color-darkslategray); 
    }

    /* Inputs de texto, email, date, file */
    input[type="text"],
    input[type="email"],
    input[type="date"],
    input[type="file"] {
        width: 100%;
        padding: 12px 15px; 
        border: 1px solid #ccc;
        border-radius: 10px; 
        margin-top: 5px;
        margin-bottom: 20px; 
        font-size: 16px;
        font-family: var(--font-inter); 
        color: var(--color-darkslategray); 
    }

    /* Checkbox group para estilo de aprendizado */
    .checkbox-group {
        margin-top: 10px;
        margin-bottom: 20px; 
        display: grid;
        grid-template-columns: repeat(2, 1fr); 
        gap: 15px; 
    }
    .checkbox-group label {
        font-weight: normal;
        display: flex;
        align-items: center;
        margin-top: 0; 
        border: 1px solid #ccc; 
        padding: 12px 15px; 
        border-radius: 10px; 
        background-color: var(--color-beige); 
        cursor: pointer;
        transition: background-color 0.3s ease, border-color 0.3s ease;
        font-family: var(--font-inter); 
        color: var(--color-darkslategray);
    }
    .checkbox-group input[type="checkbox"] {
        width: auto; 
        margin-right: 10px; 
        
    }
    .checkbox-group label:hover {
        background-color: #e0e0d0; 
        border-color: var(--color-darkcyan);
    }

    /* Botão de submit */
    input[type="submit"] {
        margin-top: 30px; 
        background-color: var(--color-darkcyan);
        color: white;
        padding: 12px 20px; 
        border: none;
        border-radius: 30px; 
        cursor: pointer;
        width: 100%;
        font-size: 18px;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    input[type="submit"]:hover {
        background-color: #007a91; 
    }

    /* Mensagem de feedback */
    .mensagem {
        text-align: center;
        margin-top: 20px;
        margin-bottom: 20px; 
        padding: 15px;
        background-color: var(--color-linen); 
        border-radius: 10px;
        font-weight: bold;
        color: var(--color-darkslategray);
        box-shadow: 0 0 5px rgba(0,0,0,0.05);
    }

    /* Estilo para a imagem de perfil */
    .profile-pic-container {
        position: absolute;
        top: 20px;
        right: 20px; 
        width: 80px; 
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        background-color: #e0e0e0;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 3px solid var(--color-darkcyan); 
        z-index: 10;
    }
    .profile-pic-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Media Queries para responsividade */
    @media (max-width: 768px) {
        .form-box {
            padding: 20px; 
            padding-top: 100px; 
            padding-right: 20px;
        }
        .profile-pic-container {
            top: 20px; 
            right: 50%; 
            transform: translateX(50%); 
            width: 100px; 
            height: 100px;
        }
        .checkbox-group {
            grid-template-columns: 1fr; 
        }
        header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        header nav {
            flex-wrap: wrap;
            justify-content: center;
        }
        header nav a {
            margin: 5px 10px;
        }
        .main-page-title {
            font-size: 36px;
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
        <a href="#sobre.php">SOBRE NÓS</a>
        <a href="cadastro.php">CADASTRO</a>
        <a href="#">FÓRUM ONLINE</a>
    </nav>
</header>

<h1 class="main-page-title">Crie Sua Conta Estrelar</h1>

<div class="form-box">
    <div class="profile-pic-container">
        <img id="previewFoto" src="https://via.placeholder.com/80?text=Foto" alt="Foto de Perfil">
    </div>

    <h2>Crie sua Conta</h2>
    <?php if ($mensagem): ?>
      <div class="mensagem"><?php echo $mensagem; ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <label for="nome">Nome completo:</label>
      <input type="text" name="nome" required>

      <label for="nascimento">Data de nascimento:</label>
      <input type="date" name="nascimento" required>

      <label for="genero">Gênero (opcional):</label>
      <input type="text" name="genero">

      <label for="email">E-mail:</label>
      <input type="email" name="email" required>

      <label for="senha">Senha:</label>
      <input type="password" name="senha" required>

      <label>Estilo de aprendizado:</label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="estilo[]" value="visual"> Visual</label>
        <label><input type="checkbox" name="estilo[]" value="auditivo"> Auditivo</label>
        <label><input type="checkbox" name="estilo[]" value="cinestesico"> Cinestésico</label>
        <label><input type="checkbox" name="estilo[]" value="leitura_escrita"> Leitura e Escrita</label>
      </div>

      <label for="foto">Foto de perfil:</label>
      <input type="file" name="foto" id="fotoInput">

      <input type="submit" value="Cadastrar">
    </form>
</div>

<script>
  // JavaScript para pré-visualizar a imagem
  document.getElementById('fotoInput').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
      const previewFoto = document.getElementById('previewFoto');
      previewFoto.src = URL.createObjectURL(file);
      previewFoto.style.display = 'block'; // Garante que a imagem seja mostrada
    } else {
      // Se nenhum arquivo for selecionado, volta para a imagem de placeholder
      document.getElementById('previewFoto').src = "https://via.placeholder.com/80?text=Foto";
    }
  });
</script>
</body>
</html>
