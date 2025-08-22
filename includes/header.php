<?php
session_start();
if (!isset($_SESSION['prof_id'])) {
    header("Location: ../dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<?php
print_r($_SESSION);
?>

<head>
    <meta charset="UTF-8">
    <title>Dashboard Professor</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <header>
        <h2>Dashboard do Professor</h2>
        <nav>
            <a href="../dashboard.php">Início</a> |
            <a href="../views/view_presencas.php">Presenças</a> |
            <a href="../views/view_turmas.php">Turmas</a> |
            <a href="../views/view_disciplinas.php">Disciplinas</a> |
            <a href="../views/view_configuracoes.php">Configurações</a> |
            <a href="../logout.php">Sair</a>
        </nav>
        <hr>
    </header>
