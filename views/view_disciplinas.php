<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_auth('professor');

$stmt = $pdo->query("SELECT * FROM disciplinas");
$disciplinas = $stmt->fetchAll();
?>

<?php
echo 'id ' . htmlspecialchars($_COOKIE["professor_id"]);
?>

<h3>Disciplinas</h3>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Nome</th>
    </tr>
    <?php foreach ($disciplinas as $disc): ?>
        <tr>
            <td><?= $disc['id'] ?></td>
            <td><?= $disc['nome'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
