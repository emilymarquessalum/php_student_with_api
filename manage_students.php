<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_auth('professor');

$prof_id = $_SESSION['prof_id'];
$access_token = $_SESSION['access_token'] ?? '';

// Get turma_id from URL
$turma_id = $_GET['turma_id'] ?? null;
if (!$turma_id) {
    echo '<div style="color:red; font-weight:bold;">Erro: turma_id não fornecido na URL.</div>';
    exit();
}

// Success and error messages
$success_message = '';
$error_message = '';
$debug_info = '';

// Data fetched from API
$class = null;
$students = [];
$available_students = [];

// API endpoint for this class 

// --- API Calls for Data and Actions ---

try {
    // Check if professor has access and fetch class details

    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $class_response = api_request("/professor/classes/$turma_id", 'GET', null, $headers);

    if (!$class_response['success']) {
        echo '<div style="color:red; font-weight:bold;">Erro: Você não tem acesso a esta turma ou ela não existe.</div>';
        exit();
    }
    $class = $class_response['data'];

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        $data = $_POST;
        $headers = [
            "Authorization: Bearer $access_token"
        ];
        $response = api_request("/student/$action", 'POST', $data, $headers);

        if ($response['success']) {
            if ($action === 'add_existing') {
                $success_message = 'Aluno adicionado à turma com sucesso!';
            } elseif ($action === 'create_new') {
                $success_message = 'Novo aluno criado e adicionado à turma com sucesso!';
            } elseif ($action === 'remove_student') {
                $success_message = 'Aluno removido da turma com sucesso!';
            }
        } else {
            $error_message = $response['message'] ?? 'Erro desconhecido.';
            $debug_info = 'Status: ' . ($response['status_code'] ?? 'N/A') . ', Details: ' . ($response['details'] ?? 'N/A');
        }
    }

    // Always fetch the latest list of students after an action
    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $students_response = api_request("/professor/classes/$turma_id/students", 'GET', null, $headers);
    if ($students_response['success']) {
        $students = $students_response['data'];
    } else {
        $error_message = 'Erro ao carregar a lista de alunos.';
        $debug_info = 'Status: ' . ($students_response['status_code'] ?? 'N/A') . ', Details: ' . ($students_response['details'] ?? 'N/A');
    }

    // Fetch available students
    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $available_students_response = api_request("/professor/classes/$turma_id/students/not-enrolled", 'GET', null, $headers);
    if ($available_students_response['success']) {
        $available_students = $available_students_response['data'];
    } else {
        $error_message .= ' Erro ao carregar alunos disponíveis.';
    }
} catch (Exception $e) {
    $error_message = 'Erro ao conectar com o servidor.';
    $debug_info = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - <?php echo htmlspecialchars($class['nome_turma']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <style>
        .student-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .student-info {
            padding: 15px;
        }

        .student-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .student-details {
            color: #666;
            font-size: 0.9rem;
        }

        .student-actions {
            display: flex;
            justify-content: flex-end;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .tab-content {
            padding: 20px 0;
        }

        .form-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            padding: 10px 20px;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom-color: #f8f9fa;
        }
    </style>
</head>

<body class="dashboard-page">
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-qrcode me-2"></i>
                Presença QR
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="view_class.php?turma_id=<?php echo htmlspecialchars($turma_id); ?>">
                    <i class="fas fa-arrow-left me-1"></i>
                    Voltar para Turma
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>
                    Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="class-header fade-in">
            <h1 class="welcome-title">
                <i class="fas fa-users-cog me-2 text-primary"></i>
                Gerenciar Alunos
            </h1>
            <p class="welcome-subtitle">
                Turma: <?php echo htmlspecialchars($class['nome_turma'] ?? 'N/A'); ?> |
                <?php echo htmlspecialchars($class['disciplina_name'] ?? 'N/A'); ?> |
                Ano <?php echo date('Y', strtotime($class['year'] ?? 'now')); ?>
            </p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>

                <?php if ($debug_info): ?>
                    <div class="mt-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="toggleDebugInfo()"
                            id="debugToggle">
                            <i class="fas fa-bug me-1"></i>
                            Mostrar Detalhes Técnicos
                        </button>
                        <div id="debugInfo" class="mt-2" style="display: none;">
                            <div class="p-2 bg-light rounded">
                                <code><?php echo htmlspecialchars($debug_info); ?></code>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="content-card fade-in">
                    <div class="card-header-modern">
                        <h3 class="card-title">
                            <i class="fas fa-user-graduate me-2"></i>
                            Alunos da Turma
                        </h3>
                        <div class="card-actions">
                            <span class="badge bg-primary">
                                <i class="fas fa-users me-1"></i>
                                <?php echo count($students); ?> alunos
                            </span>
                        </div>
                    </div>
                    <div class="card-body-modern">
                        <?php if (empty($students)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum aluno matriculado</h5>
                                <p class="text-muted mb-4">Esta turma ainda não possui alunos matriculados.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($students as $student): ?>
                                    <div class="col-md-6">
                                        <div class="student-card">
                                            <div class="student-info">
                                                <div class="student-name">
                                                    <i class="fas fa-user-graduate me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($student['name'] ?? 'N/A'); ?>
                                                </div>
                                                <div class="student-details">
                                                    <div><i class="fas fa-id-card me-2"></i> <?php echo htmlspecialchars($student['matricula'] ?? 'N/A'); ?></div>
                                                    <div><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                            <div class="student-actions">
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja remover este aluno da turma?');">
                                                    <input type="hidden" name="action" value="remove_student">
                                                    <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($student['id']); ?>">
                                                    <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($turma_id); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-user-minus me-1"></i>
                                                        Remover da Turma
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="content-card fade-in">
                    <div class="card-header-modern">
                        <h3 class="card-title">
                            <i class="fas fa-user-plus me-2"></i>
                            Adicionar Alunos
                        </h3>
                    </div>
                    <div class="card-body-modern">
                        <ul class="nav nav-tabs" id="addStudentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing" type="button" role="tab">
                                    <i class="fas fa-user-check me-1"></i>
                                    Aluno Existente
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab">
                                    <i class="fas fa-user-plus me-1"></i>
                                    Novo Aluno
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="addStudentTabsContent">
                            <div class="tab-pane fade show active" id="existing" role="tabpanel">
                                <div class="form-section">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_existing">
                                        <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($turma_id); ?>">
                                        <div class="mb-3">
                                            <label for="aluno_id" class="form-label">
                                                <i class="fas fa-user me-1"></i>
                                                Selecione um Aluno
                                            </label>
                                            <select class="form-select" id="aluno_id" name="aluno_id" required>
                                                <option value="">Escolha um aluno...</option>
                                                <?php foreach ($available_students as $student): ?>
                                                    <option value="<?php echo htmlspecialchars($student['id']); ?>">
                                                        <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['matricula']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (empty($available_students)): ?>
                                                <div class="form-text text-warning">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Não há alunos disponíveis para adicionar.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100" <?php echo empty($available_students) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus-circle me-1"></i>
                                            Adicionar à Turma
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="new" role="tabpanel">
                                <div class="form-section">

                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_new">
                                        <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($turma_id); ?>">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">
                                                <i class="fas fa-user me-1"></i>
                                                Nome Completo
                                            </label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-1"></i>
                                                Email
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="matricula" class="form-label">
                                                <i class="fas fa-id-card me-1"></i>
                                                Matrícula
                                            </label>
                                            <input type="text" class="form-control" id="matricula" name="matricula" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="senha" class="form-label">
                                                <i class="fas fa-lock me-1"></i>
                                                Senha
                                            </label>
                                            <input type="password" class="form-control" id="senha" name="senha" value="123456">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Senha padrão: 123456
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-user-plus me-1"></i>
                                            Criar e Adicionar à Turma
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDebugInfo() {
            const debugInfo = document.getElementById('debugInfo');
            const debugToggle = document.getElementById('debugToggle');

            if (debugInfo.style.display === 'none') {
                debugInfo.style.display = 'block';
                debugToggle.innerHTML = '<i class="fas fa-bug me-1"></i> Ocultar Detalhes Técnicos';
            } else {
                debugInfo.style.display = 'none';
                debugToggle.innerHTML = '<i class="fas fa-bug me-1"></i> Mostrar Detalhes Técnicos';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.5s ease';

                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>

</html>