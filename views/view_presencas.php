<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_auth('professor');

$stmt = $pdo->prepare("SELECT * FROM presencas WHERE professor_id = ?");
$stmt->execute([$_SESSION['prof_id']]);
$presencas = $stmt->fetchAll();
?>

<h3>Lista de Presenças</h3>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Data</th>
        <th>Aluno</th>
        <th>Turma</th>
        <th>Disciplina</th>
    </tr>
    <?php foreach ($presencas as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= $p['data'] ?></td>
            <td><?= $p['aluno_nome'] ?></td>
            <td><?= $p['turma'] ?></td>
            <td><?= $p['disciplina'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>