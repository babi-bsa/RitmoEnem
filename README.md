# RitmoEnem
TABELA USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    nascimento DATE,
    genero VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    senha VARCHAR(255),
    estilos TEXT,
    foto VARCHAR(255)
);

TABELA CRONOGRAMAS
CREATE TABLE cronogramas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    horas_por_dia INT,
    horario_inicio TIME,
    horario_fim TIME,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

TABELA DIAS
CREATE TABLE dias_estudo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cronograma_id INT,
    dia_semana VARCHAR(10),
    FOREIGN KEY (cronograma_id) REFERENCES cronogramas(id) ON DELETE CASCADE
);

TABELA MATERIAS DIFICULDADES
CREATE TABLE dificuldades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cronograma_id INT,
    materia VARCHAR(100),
    nivel VARCHAR(20),
    FOREIGN KEY (cronograma_id) REFERENCES cronogramas(id) ON DELETE CASCADE
);

TABELA CRONOGRAMA GERADO
CREATE TABLE cronograma_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cronograma_id INT,
    dia VARCHAR(10),
    hora INT,
    materia VARCHAR(100),
    dificuldade VARCHAR(20),
    FOREIGN KEY (cronograma_id) REFERENCES cronogramas(id) ON DELETE CASCADE
);
