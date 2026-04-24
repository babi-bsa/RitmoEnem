<?php
$conn = new mysqli("localhost", "root", "", "ritmoenem");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
