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
    header('Location: dashboard.php');
    exit();
}

$erro = '';
$success_message = '';
$class = null;

try {
    $headers = [
        "Authorization: Bearer $access_token"
    ];
    $class_response = api_request("/professor/classes/$turma_id/info", 'GET', null, $headers);

    if (!$class_response['success'] || empty($class_response['data'])) {
        header('Location: dashboard.php');
        exit();
    }
    $class = $class_response['data']['class'];
} catch (Exception $e) {
    error_log("Error fetching class: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}

// Handle form submission to create a new class day via API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_aula = $_POST['data_aula'] ?? '';
    $hora_aula = $_POST['hora_aula'] ?? '';

    // Validation
    if (empty($data_aula) || empty($hora_aula)) {
        $erro = "Data e horário são obrigatórios.";
    } else {
        try {
            $headers = [
                "Authorization: Bearer $access_token"
            ];
            $response = api_request("/professor/classes/$turma_id/days", 'POST', [
                'turma_id' => $turma_id,
                'data_aula' => $data_aula,
                'hora_aula' => $hora_aula
            ], $headers);

            if ($response['success']) {
                $success_message = "Aula registrada com sucesso!";
                $dia_aula_id = $response['data']['id'];
                $turma_id = $response['data']['turma_id'];

                // Redirect to QR code page after a short delay
                header("refresh:2;url=qr_presenca.php?dia_aula_id=$dia_aula_id&turma_id=$turma_id");
            } else {
                $erro = $response['message'] ?? 'Erro desconhecido ao registrar a aula.';
            }
        } catch (Exception $e) {
            $erro = "Erro de conexão: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Aula - <?php echo htmlspecialchars($class['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/register-class.css" rel="stylesheet">
</head>

<body class="page-layout gradient">
    <nav class="navbar">
        <div class="container flex justify-between items-center">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-qrcode me-2"></i>
                Presença QR
            </a>
            <div class="flex gap-md">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>
                    Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-xl">
        <div class="flex justify-center">
            <div style="max-width: 600px; width: 100%;">
                <div class="page-header fade-in card">
                    <div class="header-content">
                        <h1 class="page-title">
                            <i class="fas fa-calendar-plus me-2"></i>
                            Registrar Nova Aula
                        </h1>
                        <p class="page-subtitle">
                            <strong><?php echo htmlspecialchars($class['nome_turma']); ?></strong> -
                            <?php echo htmlspecialchars($class['disciplina']['name']); ?>
                        </p>
                    </div>

                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <div class="mt-sm text-sm">
                                    <i class="fas fa-spinner spin me-1"></i>
                                    Redirecionando para gerar QR Code...
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($erro): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($erro); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="registration-form" autocomplete="off">
                            <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($turma_id); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="data_aula" class="form-label">
                                            <i class="fas fa-calendar"></i>
                                            Data da Aula
                                        </label>
                                        <input type="date"
                                            id="data_aula"
                                            name="data_aula"
                                            class="form-control"
                                            value="<?php echo $_POST['data_aula'] ?? date('Y-m-d'); ?>"
                                            required>
                                        <div class="form-hint">
                                            <i class="fas fa-info-circle"></i>
                                            Selecione a data da aula
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hora_aula" class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Horário da Aula
                                        </label>
                                        <input type="time"
                                            id="hora_aula"
                                            name="hora_aula"
                                            class="form-control"
                                            value="<?php echo $_POST['hora_aula'] ?? date('H:i'); ?>"
                                            required>
                                        <div class="form-hint">
                                            <i class="fas fa-info-circle"></i>
                                            Horário de início da aula
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <label class="form-label">
                                    <i class="fas fa-check-double"></i>
                                    Método de Confirmação de Presença
                                </label>
                                <div class="d-flex gap-3 flex-wrap" id="attendance-method-group">
                                    <label class="btn btn-outline-primary attendance-method-option active" data-value="qr">
                                        <input type="radio" name="attendance_method" value="qr" checked style="display:none;">
                                        <i class="fas fa-qrcode me-2"></i> QR Code
                                    </label>
                                    <label class="btn btn-outline-secondary attendance-method-option disabled" data-value="manual">
                                        <input type="radio" name="attendance_method" value="manual" disabled style="display:none;">
                                        <i class="fas fa-keyboard me-2"></i> Inserção Manual (WIP)
                                    </label>
                                    <label class="btn btn-outline-warning attendance-method-option disabled" style="pointer-events:none;opacity:0.6;" data-value="face">
                                        <input type="radio" name="attendance_method" value="face" disabled style="display:none;">
                                        <i class="fas fa-user-astronaut me-2"></i> Reconhecimento Facial (WIP)
                                    </label>
                                </div>
                                <div class="form-hint mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    "Inserção Manual" e "Reconhecimento Facial" está em desenvolvimento e será lançado em breve.
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-graduation-cap"></i>
                                    Informações da Turma
                                </label>
                                <div class="professor-info">
                                    <div class="professor-avatar">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="professor-details">
                                        <div class="professor-name">
                                            <?php echo htmlspecialchars($class['nome_turma']); ?>
                                        </div>
                                        <div class="professor-email">
                                            <?php echo htmlspecialchars($class['disciplina']['name']); ?> -
                                            <?php echo date('Y', strtotime($class['year'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="submit-attendance-btn">
                                    <i class="fas fa-qrcode" id="submit-attendance-icon"></i>
                                    Registrar e Gerar QR
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card enhanced help-card fade-in">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-lightbulb"></i>
                            Como Funciona
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="help-items">
                            <div class="help-item">
                                <i class="fas fa-qrcode text-primary"></i>
                                <div class="help-content">
                                    <strong>QR Code Único:</strong>
                                    <span>Cada aula terá seu próprio código QR para controle de presença</span>
                                </div>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-mobile-alt text-success"></i>
                                <div class="help-content">
                                    <strong>Fácil de Usar:</strong>
                                    <span>Alunos escaneiam com qualquer celular para marcar presença</span>
                                </div>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-chart-bar text-warning"></i>
                                <div class="help-content">
                                    <strong>Relatórios:</strong>
                                    <span>Acompanhe a frequência e gere relatórios de presença</span>
                                </div>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-clock text-info"></i>
                                <div class="help-content">
                                    <strong>Simples e Rápido:</strong>
                                    <span>Apenas defina data e horário para criar uma aula</span>
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
        // Set minimum date and time
        const dateInput = document.querySelector('input[name="data_aula"]');
        const timeInput = document.querySelector('input[name="hora_aula"]');

        dateInput.min = new Date().toISOString().split('T')[0];

        // If selected date is today, set minimum time to current time
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();

            if (selectedDate.toDateString() === today.toDateString()) {
                const currentTime = today.toTimeString().slice(0, 5);
                timeInput.min = currentTime;
            } else {
                timeInput.removeAttribute('min');
            }
        });

        // Add class based on current time
        window.onload = function() {
            const date = new Date();
            const currentTime = date.toTimeString().slice(0, 5);
            const timeInput = document.getElementById('hora_aula');
            if (date.toISOString().split('T')[0] === dateInput.value) {
                timeInput.min = currentTime;
            }
        };

        // Fade-in animation
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

        // Attendance method highlight logic & dynamic button text
        const btn = document.getElementById('submit-attendance-btn');
        const btnIcon = document.getElementById('submit-attendance-icon');

        function updateButtonText(method) {
            if (method === 'qr') {
                btn.innerHTML = '<i class="fas fa-qrcode" id="submit-attendance-icon"></i> Registrar e Gerar QR';
            } else if (method === 'manual') {
                btn.innerHTML = '<i class="fas fa-keyboard" id="submit-attendance-icon"></i> Registrar Presença Manual';
            } else if (method === 'face') {
                btn.innerHTML = '<i class="fas fa-user-astronaut" id="submit-attendance-icon"></i> Registrar com Reconhecimento Facial';
            }
        }
        document.querySelectorAll('.attendance-method-option:not(.disabled)').forEach(label => {
            label.addEventListener('click', function() {
                document.querySelectorAll('.attendance-method-option').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                updateButtonText(radio.value);
            });
        });
        const checkedRadio = document.querySelector('input[name="attendance_method"]:checked');
        if (checkedRadio) updateButtonText(checkedRadio.value);
    </script>
</body>

</html>