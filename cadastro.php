<?php
session_start();
require_once 'conexao.php';
// Processa o formulário
$mensagem = "";
$cadastroSucesso=false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
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

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $mensagem="Poxa, este e-mail já está cadastrado. Faça seu login!";
        }else{
            $senhaHash=password_hash($senha, PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO usuarios (nome,email,senha,foto,estilos) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss",$nome,$email,$senhaHash,$foto,$estilos);
            
            if ($stmt->execute()) {
                $cadastroSucesso=true;
            } else {
                $mensagem = "Erro ao cadastrar: " . $stmt->error;
                $cadastroSucesso=false;
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

    .form-box {
        max-width: 620px;
        margin: 40px auto 0;
        background-color: var(--color-linen);
        padding: 40px;
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        font-family: var(--font-kodchasan);
        position: relative;
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
    input[type="password"],
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

    /*Efeito ao selecionar algum input*/
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="date"]:focus,
    input[type="file"]:focus {
        border-color: var(--color-darkcyan);
        outline: none;
        box-shadow: 0 0 3px rgba(0, 151, 178, 0.5);
    } 

    input[type="password"]{
        letter-spacing: 2px;
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

    footer {
        background-color: var(--color-darkcyan);
        color: white;
        text-align: center;
        padding: 20px 10px;
        font-family: var(--font-inter);
        margin-top: 80px;
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
    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(8, 15, 30, 0.82);
        backdrop-filter: blur(6px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeOverlay .4s ease;
    }
    
    @keyframes fadeOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .modal-box {
        background: rgba(242, 239,231,0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        width: 90%;
        max-width: 450px;
        padding: 45px;
        border-radius: 30px;
        text-align: center;
        position: relative;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
        animation: modalPop .5s ease;
    }
    
    @keyframes modalPop {
        from {
            transform: scale(.8);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes pulse {
        0%{
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(0, 151, 178, .6);
        }
        70%{
            transform: scale(1.05);
            box-shadow: 0 0 0 20px rgba(0, 151, 178, 0);
        }
        100%{
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(0, 151, 178, .6);
        }
    }
    
    .modal-icon {
        width: 90px;
        height: 90px;
        margin: 0 auto 25px;
        border-radius: 50%;
        background: linear-gradient( 135deg, #00c6d7, var(--color-darkcyan));
        display: flex;
        justify-content: center;
        align-items: center;
        animation: pulse 2s infinite;
    }
    
    .modal-icon svg {
        width: 45px;
        height: 45px;
        stroke: #fff;
        fill: none;
        stroke-width: 3;
    }
    .modal-title {
        font-family: var(--font-limelight);
        color: var(--color-darkslateblue);
        font-size: 30px;
        margin-bottom: 15px;
    }
    .modal-sub {
        color: var(--color-darkslategray);
        line-height: 1.7;
        margin-bottom: 30px;
    }
    .modal-btn {
        display: inline-block;
        text-decoration: none;
        background: var(--color-darkcyan);
        color: #fff;
        padding: 14px 35px;
        border-radius: 50px;
        font-family: var(--font-kodchasan);
        font-weight: bold;
        transition: .3s;
    }
    .modal-btn:hover {
        background: #007a91;
        transform: translateY(-2px);
    }

    .modal-open{
        overflow: hidden;
    }

    .sparkle {
        position: absolute;
        font-size: 18px;
        color: var(--color-darkcyan);
        opacity: .6;
    }
    
    .sparkle:nth-child(1){
        top:15px;
        left:20px;
    }
    
    .sparkle:nth-child(2){
        top:20px;
        right:20px;
    }
    
    .sparkle:nth-child(3){
        bottom:20px;
        left:25px;
    }
    
    .sparkle:nth-child(4){
        bottom:15px;
        right:25px;
}
  </style>
</head>
<body class="<?php echo $cadastroSucesso ? 'modal-open' : ''; ?>">
<header>
    <div class="logo">Ritmo Enem</div>
    <nav>
        <a href="home.php">HOME</a>
        <a href="cronograma.php">CRONOGRAMA</a>
        <a href="sobre.php">SOBRE NÓS</a>
        <a href="cadastro.php" class="active">CADASTRO</a>
        
    </nav>
</header>

<section class="page-header">
    <h1>Crie Sua Conta</h1>
    <p>Cadastre-se para começar a organizar seus estudos com o Ritmo Enem.</p>
</section>

<div class="form-box">
    <div class="profile-pic-container">
        <img id="previewFoto" src="https://via.placeholder.com/80?text=Foto" alt="Foto de Perfil">
    </div>

    <h2>Crie sua Conta</h2>
    <?php if ($mensagem && !$cadastroSucesso): ?>
      <div class="mensagem"><?php echo $mensagem; ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <label for="nome">Nome completo:</label>
      <input type="text" name="nome" required>
      
      <label for="email">E-mail:</label>
      <input type="email" name="email" required>

      <label for="senha">Senha:</label>
      <input type="password" name="senha" id="senha" minlength="8" required>

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
<?php if ($cadastroSucesso): ?>
    <div class="overlay" id="modal">
        <div class="modal-box">

            <span class="sparkle">✦</span>
            <span class="sparkle">✦</span>
            <span class="sparkle">✦</span>
            <span class="sparkle">✦</span>

            <div class="modal-icon">
                <svg viewBox="0 0 24 24">
                    <polyline points="4,13 9,18 20,7"/>
                </svg>
            </div>
            <h2 class="modal-title">Cadastro Realizado!</h2>
            <p class="modal-sub">
                Bem-vindo ao <strong>Ritmo Enem</strong> ✨<br>
                Sua conta foi criada com sucesso.<br>
                Agora é hora de arrasar nos estudos!
            </p>
            <a href="home.php" class="modal-btn">
                Ir para o início →
            </a>
        </div>
    </div>
<?php endif; ?>

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
<footer>
    <p>Siga-nos: @ritmoEnem no Instagram e Twitter</p>
</footer>
</body>
</html>
