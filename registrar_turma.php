
<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_auth('professor');
$prof_id = $_SESSION['prof_id'];
$success_message = '';
$error_message = '';
$debug_info = '';
 
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_turma = trim($_POST['nome_turma'] ?? '');
    $disciplina_id = trim($_POST['disciplina_id'] ?? '');
    $year = trim($_POST['year'] ?? '');
    
    // Validation
    if (empty($nome_turma) || empty($disciplina_id) || empty($year)) {
        $error_message = 'Todos os campos são obrigatórios.';
    } else {
        try {
            // Generate unique turma ID
            $turma_id = 'turma-' . uniqid();
            $year_timestamp = $year . '-01-01 00:00:00';
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Debug: Log the values being inserted
            $debug_info = "Tentando inserir: ID={$turma_id}, Nome={$nome_turma}, Disciplina={$disciplina_id}, Ano={$year_timestamp}";
            
            // Insert new turma
            $stmt = $pdo->prepare("
                INSERT INTO turma (id, nome_turma, disciplina_id, year) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$turma_id, $nome_turma, $disciplina_id, $year_timestamp]);
            
            // Add professor to the turma (schema: professor_id, turma_id, tipo)
            $stmt = $pdo->prepare("
                INSERT INTO integrante_da_turma (turma_id, professor_id, tipo) 
                VALUES (?, ?, 'professor')
            ");
            $stmt->execute([$turma_id, $prof_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = 'Turma criada com sucesso!';
             
            // Clear debug info on success
            $debug_info = '';
            
            // Redirect after success
            header('refresh:2;url=dashboard.php');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Erro ao criar turma. Tente novamente.';
            
            // Store detailed error information for debugging
            $debug_info = "Erro SQL: " . $e->getMessage() . " | Código: " . $e->getCode();
            
            // Log error for server-side debugging
            error_log("Database error in registrar_turma.php: " . $e->getMessage());
        }
    }
}

// Get available disciplines
try {
    $stmt = $pdo->prepare("SELECT id, name FROM disciplina ORDER BY name");
    $stmt->execute();
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    $disciplinas = [];
    error_log("Error fetching disciplines: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nova Turma - Sistema de Presença QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/register-class.css" rel="stylesheet">
</head>
<body class="page-layout gradient">
    <!-- Navigation -->
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
                <!-- Page Header -->
                <div class="page-header fade-in card" style="padding: 1.5rem;">>
                    <div class="header-content">
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle me-2"></i>
                            Registrar Nova Turma
                        </h1>
                        <p class="page-subtitle">
                            Crie uma nova turma para gerenciar presença e aulas
                    </div> 
                    
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <div class="mt-sm text-sm">
                                    <i class="fas fa-spinner spin me-1"></i>
                                    Redirecionando para o dashboard...
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                
                                <?php if ($debug_info): ?>
                                    <div class="mt-md">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline" 
                                                onclick="toggleDebugInfo()"
                                                id="debugToggle">
                                            <i class="fas fa-bug me-1"></i>
                                            Mostrar Detalhes Técnicos
                                        </button>
                                        <div id="debugInfo" class="debug-details mt-sm" style="display: none;">
                                            <div class="debug-content">
                                                <strong>Informações para Debugging:</strong>
                                                <code><?php echo htmlspecialchars($debug_info); ?></code>
                                                <div class="mt-sm text-sm text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Compartilhe essas informações com o desenvolvedor se o problema persistir.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="registration-form">
                            <!-- Nome da Turma -->
                            <div class="form-group">
                                <label for="nome_turma" class="form-label">
                                    <i class="fas fa-users"></i>
                                    Nome da Turma
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome_turma" 
                                       name="nome_turma" 
                                       placeholder="Ex: 3º Ano A, Turma Manhã, etc."
                                       value="<?php echo htmlspecialchars($_POST['nome_turma'] ?? ''); ?>"
                                       required>
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Escolha um nome descritivo para a turma
                                </div>
                            </div>

                            <!-- Disciplina -->
                            <div class="form-group">
                                <label for="disciplina_id" class="form-label">
                                    <i class="fas fa-book"></i>
                                    Disciplina
                                </label>
                                <select class="form-control" 
                                        id="disciplina_id" 
                                        name="disciplina_id" 
                                        required>
                                    <option value="">Selecione uma disciplina</option>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                        <option value="<?php echo htmlspecialchars($disciplina['id']); ?>"
                                                <?php echo (($_POST['disciplina_id'] ?? '') === $disciplina['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($disciplina['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Selecione a matéria que você irá lecionar
                                </div>
                            </div>

                            <!-- Ano Letivo -->
                            <div class="form-group">
                                <label for="year" class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Ano Letivo
                                </label>
                                <select class="form-control" 
                                        id="year" 
                                        name="year" 
                                        required>
                                    <option value="">Selecione o ano</option>
                                    <?php 
                                    $current_year = date('Y');
                                    $selected_year = $_POST['year'] ?? $current_year;
                                    for ($i = $current_year - 1; $i <= $current_year + 2; $i++): 
                                    ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo ($selected_year == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Ano letivo da turma
                                </div>
                            </div>

                            <!-- Professor Info (Read-only) -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    Professor Responsável
                                </label>
                                <div class="professor-info">
                                    <div class="professor-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="professor-details">
                                        <div class="professor-name">
                                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                                        </div>
                                        <div class="professor-email">
                                            <?php echo htmlspecialchars($_SESSION['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Criar Turma
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="card enhanced help-card fade-in">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-question-circle"></i>
                            Precisa de Ajuda?
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="help-items">
                            <div class="help-item">
                                <i class="fas fa-lightbulb text-primary"></i>
                                <div class="help-content">
                                    <strong>Nome da Turma:</strong>
                                    <span>Use nomes descritivos como "3º Ano A" ou "Turma Manhã"</span>
                                </div>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-lightbulb text-primary"></i>
                                <div class="help-content">
                                    <strong>Disciplina:</strong>
                                    <span>A disciplina define a matéria que será lecionada</span>
                                </div>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-lightbulb text-primary"></i>
                                <div class="help-content">
                                    <strong>Após criar:</strong>
                                    <span>Você poderá registrar aulas e gerenciar presença</span>
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
        // Toggle debug information
        function toggleDebugInfo() {
            const debugInfo = document.getElementById('debugInfo');
            const toggleBtn = document.getElementById('debugToggle');
            
            if (debugInfo.style.display === 'none') {
                debugInfo.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-bug me-1"></i>Ocultar Detalhes Técnicos';
            } else {
                debugInfo.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-bug me-1"></i>Mostrar Detalhes Técnicos';
            }
        }

        // Form validation
        document.querySelector('.registration-form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Por favor, preencha todos os campos obrigatórios.', 'warning');
            }
        });

        // Real-time validation
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                if (this.hasAttribute('required') && this.value.trim()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.querySelector('.registration-form');
            form.insertBefore(alertDiv, form.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

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
    </script>
</body>
</html>