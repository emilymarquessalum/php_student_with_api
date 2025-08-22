<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/auth.php';
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';


require_auth('student');



$aluno_id = $_SESSION['aluno_id'] ?? null;
$dia_aula_id = $_GET['dia_aula_id'] ?? null;


if (!$dia_aula_id) {
    echo '<div style="color:red; font-weight:bold;">Erro: Parâmetros ausentes. aluno_id=' . htmlspecialchars($aluno_id) . ' dia_aula_id=' . htmlspecialchars($dia_aula_id) . '</div>';
    echo '<pre>SESSION DEBUG: ' . print_r($_SESSION, true) . '</pre>';
    exit;
}

$error_debug = null;

try {
    // Check if already marked
    $stmt = $pdo->prepare('SELECT id FROM presenca WHERE dia_aula_id = ? AND aluno_id = ?');
    $stmt->execute([$dia_aula_id, $aluno_id]);
    $already = $stmt->fetch();
} catch (PDOException $e) {
    $error_debug = 'Erro ao consultar presença: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($already) && !$error_debug) {
    try {
        $stmt = $pdo->prepare('INSERT INTO presenca (id, dia_aula_id, aluno_id, timestamp) VALUES (?, ?, ?, NOW())');
        if ($stmt->execute([uniqid('pres-'), $dia_aula_id, $aluno_id])) {
            $success = true;
        } else {
            $error = 'Erro ao registrar presença.';
        }
    } catch (PDOException $e) {
        $error_debug = 'Erro ao registrar presença: ' . $e->getMessage();
    }
}

try {
    // Get class info
    $stmt = $pdo->prepare('SELECT da.*, t.nome_turma, d.name as disciplina_name FROM dia_de_aula da JOIN turma t ON da.turma_id = t.id JOIN disciplina d ON t.disciplina_id = d.id WHERE da.id = ?');
    $stmt->execute([$dia_aula_id]);
    $class_day = $stmt->fetch();
} catch (PDOException $e) {
    $error_debug = 'Erro ao buscar informações da aula: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Marcar Presença</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Marcar Presença</h2>
        <?php if ($error_debug): ?>
            <div class="alert alert-danger"><strong>Erro de banco de dados:</strong> <?php echo htmlspecialchars($error_debug); ?></div>
        <?php elseif (!$class_day): ?>
            <div class="alert alert-danger">Aula não encontrada.</div>
        <?php else: ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title"><?php echo htmlspecialchars($class_day['nome_turma']); ?></h4>
                    <p class="card-text">
                        <?php echo htmlspecialchars($class_day['disciplina_name']); ?> <br>
                        <?php echo date('d/m/Y H:i', strtotime($class_day['data'])); ?>
                    </p>
                </div>
            </div>
            <?php if (isset($success)): ?>
                <div class="alert alert-success">Presença registrada com sucesso!</div>
            <?php elseif ($already): ?>
                <div class="alert alert-info">Você já registrou presença nesta aula.</div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <form method="post">
                    <button type="submit" class="btn btn-success">Confirmar Presença</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        <a href="../dashboard.php" class="btn btn-link mt-3">Voltar ao Dashboard</a>
    </div>
</body>

</html>