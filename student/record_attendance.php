<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config.php';
require_once '../includes/functions.php';

require_auth('aluno');

$aluno_id = $_SESSION['aluno_id'] ?? null;
$turma_id = $_GET['turma_id'] ?? null;

$access_token = $_SESSION['access_token'] ?? '';
$dia_aula_id = $_GET['dia_aula_id'] ?? null;

if (!$dia_aula_id || !$aluno_id) {
    echo '<div style="color:red; font-weight:bold;">Erro: Parâmetros de acesso ausentes ou inválidos.</div>';
    exit;
}

$success = false;
$error = '';
$class_day = null;
$already_marked = false;
$error_debug = null;

try {
    // Check if the student has already marked attendance via API
    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $check_response = api_request("/student/attendance/$dia_aula_id/check", 'GET', null, $headers);
    if ($check_response['success']) {
        $already_marked =         $check_response['data']['data'];
    } else {
        $error_debug = 'Erro ao verificar presença: ' . ($check_response['message'] ?? 'Detalhes não disponíveis de presença.');
    }


    // Handle form submission to record attendance via API
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_marked) {
        $headers = [
            "Authorization: Bearer $access_token"
        ];
        $response = api_request("/attendance/mark", 'POST', [
            'dia_aula_id' => $dia_aula_id,
            'action' => 'marcar_presenca'
        ], $headers);

        if ($response['success']) {
            $success = true;
            $already_marked = true; // Update state after successful registration
        } else {
            $error = $response['message'] ?? 'Erro desconhecido ao registrar presença.';
            $error_debug = 'Erro API: ' . ($response['message'] ?? 'Detalhes não disponíveis.');
        }
    }

    // Get class info from the API
    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $class_day_response = api_request("/student/classes/$turma_id/day/$dia_aula_id", 'GET', null, $headers);
    if ($class_day_response['success']) {
        $class_day = $class_day_response['data'];
    } else {
        $error_debug = 'Erro ao buscar informações da aula: ' . ($class_day_response['message'] ?? 'Detalhes não disponíveis de aula.');
    }
} catch (Exception $e) {
    $error_debug = 'Erro de conexão com a API: ' . $e->getMessage();
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
                    <p class="card-text">
                        <?php echo date('d/m/Y H:i', strtotime($class_day['data'])); ?>
                    </p>
                </div>
            </div>
            <?php if ($success): ?>
                <div class="alert alert-success">Presença registrada com sucesso!</div>
            <?php elseif ($already_marked): ?>
                <div class="alert alert-info">Você já registrou presença nesta aula.</div>
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