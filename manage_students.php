<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_auth('professor');
$prof_id = $_SESSION['prof_id'];

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

// Verify professor has access to this class
try {
    $stmt = $pdo->prepare(" 
        SELECT t.id, t.nome_turma, d.name as disciplina_name, t.year
        FROM turma t
        JOIN disciplina d ON t.disciplina_id = d.id
        JOIN integrante_da_turma i ON t.id = i.turma_id
        WHERE t.id = ? AND i.professor_id = ? AND i.tipo = 'professor'
    ");
    $stmt->execute([$turma_id, $prof_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo '<div style="color:red; font-weight:bold;">Erro: Você não tem acesso a esta turma ou ela não existe.</div>';
        exit();
    }
    
    // Get students in this class
    $stmt = $pdo->prepare("
        SELECT 
            a.id as aluno_id,
            a.name as student_name,
            a.matricula,
            a.email
        FROM integrante_da_turma i
        JOIN aluno a ON i.aluno_id = a.id
        WHERE i.turma_id = ? AND i.tipo = 'aluno'
        ORDER BY a.name
    ");
    $stmt->execute([$turma_id]);
    $students = $stmt->fetchAll();
    
    // Handle form submission for adding existing student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_existing') {
        $aluno_id = trim($_POST['aluno_id'] ?? '');
        
        if (empty($aluno_id)) {
            $error_message = 'Por favor, selecione um aluno.';
        } else {
            try {
                // Check if student is already in the class
                $stmt = $pdo->prepare("SELECT id FROM integrante_da_turma WHERE turma_id = ? AND aluno_id = ?");
                $stmt->execute([$turma_id, $aluno_id]);
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Este aluno já está matriculado nesta turma.';
                } else {
                    // Add student to class
                    $stmt = $pdo->prepare("INSERT INTO integrante_da_turma (turma_id, aluno_id, tipo) VALUES (?, ?, 'aluno')");
                    $stmt->execute([$turma_id, $aluno_id]);
                    $success_message = 'Aluno adicionado à turma com sucesso!';
                    
                    // Refresh student list
                    $stmt = $pdo->prepare("
                        SELECT 
                            a.id as aluno_id,
                            a.name as student_name,
                            a.matricula,
                            a.email
                        FROM integrante_da_turma i
                        JOIN aluno a ON i.aluno_id = a.id
                        WHERE i.turma_id = ? AND i.tipo = 'aluno'
                        ORDER BY a.name
                    ");
                    $stmt->execute([$turma_id]);
                    $students = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                $error_message = 'Erro ao adicionar aluno à turma.';
                $debug_info = "Erro SQL: " . $e->getMessage() . " | Código: " . $e->getCode();
                error_log("Database error in manage_students.php: " . $e->getMessage());
            }
        }
    }
    
    // Handle form submission for creating new student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_new') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $senha = trim($_POST['senha'] ?? '123456'); // Default password
        
        if (empty($name) || empty($email) || empty($matricula)) {
            $error_message = 'Todos os campos são obrigatórios.';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Check if email already exists in usuario table
                $stmt = $pdo->prepare("SELECT email FROM usuario WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() === 0) {
                    // Create new usuario
                    $stmt = $pdo->prepare("INSERT INTO usuario (email, senha) VALUES (?, ?)");
                    $stmt->execute([$email, $senha]);
                }
                
                // Check if student already exists
                $stmt = $pdo->prepare("SELECT id FROM aluno WHERE email = ? OR matricula = ?");
                $stmt->execute([$email, $matricula]);
                $existing_student = $stmt->fetch();
                
                if ($existing_student) {
                    $aluno_id = $existing_student['id'];
                    $error_message = 'Um aluno com este email ou matrícula já existe.';
                    $pdo->rollBack();
                } else {
                    // Create new aluno
                    $aluno_id = 'aluno-' . uniqid();
                    $stmt = $pdo->prepare("INSERT INTO aluno (id, email, matricula, name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$aluno_id, $email, $matricula, $name]);
                    
                    // Add student to class
                    $stmt = $pdo->prepare("INSERT INTO integrante_da_turma (turma_id, aluno_id, tipo) VALUES (?, ?, 'aluno')");
                    $stmt->execute([$turma_id, $aluno_id]);
                    
                    $pdo->commit();
                    $success_message = 'Novo aluno criado e adicionado à turma com sucesso!';
                    
                    // Refresh student list
                    $stmt = $pdo->prepare("
                        SELECT 
                            a.id as aluno_id,
                            a.name as student_name,
                            a.matricula,
                            a.email
                        FROM integrante_da_turma i
                        JOIN aluno a ON i.aluno_id = a.id
                        WHERE i.turma_id = ? AND i.tipo = 'aluno'
                        ORDER BY a.name
                    ");
                    $stmt->execute([$turma_id]);
                    $students = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = 'Erro ao criar novo aluno.';
                $debug_info = "Erro SQL: " . $e->getMessage() . " | Código: " . $e->getCode();
                error_log("Database error in manage_students.php: " . $e->getMessage());
            }
        }
    }
    
    // Handle student removal
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_student') {
        $aluno_id = trim($_POST['aluno_id'] ?? '');
        
        if (empty($aluno_id)) {
            $error_message = 'ID do aluno não fornecido.';
        } else {
            try {
                // Remove student from class
                $stmt = $pdo->prepare("DELETE FROM integrante_da_turma WHERE turma_id = ? AND aluno_id = ? AND tipo = 'aluno'");
                $stmt->execute([$turma_id, $aluno_id]);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = 'Aluno removido da turma com sucesso!';
                    
                    // Refresh student list
                    $stmt = $pdo->prepare("
                        SELECT 
                            a.id as aluno_id,
                            a.name as student_name,
                            a.matricula,
                            a.email
                        FROM integrante_da_turma i
                        JOIN aluno a ON i.aluno_id = a.id
                        WHERE i.turma_id = ? AND i.tipo = 'aluno'
                        ORDER BY a.name
                    ");
                    $stmt->execute([$turma_id]);
                    $students = $stmt->fetchAll();
                } else {
                    $error_message = 'Aluno não encontrado na turma.';
                }
            } catch (PDOException $e) {
                $error_message = 'Erro ao remover aluno da turma.';
                $debug_info = "Erro SQL: " . $e->getMessage() . " | Código: " . $e->getCode();
                error_log("Database error in manage_students.php: " . $e->getMessage());
            }
        }
    }
    
    // Get all students not in this class for the dropdown
    $stmt = $pdo->prepare("
        SELECT a.id, a.name, a.matricula, a.email
        FROM aluno a
        WHERE a.id NOT IN (
            SELECT i.aluno_id FROM integrante_da_turma i 
            WHERE i.turma_id = ? AND i.tipo = 'aluno'
        )
        ORDER BY a.name
    ");
    $stmt->execute([$turma_id]);
    $available_students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div style="color:red; font-weight:bold;">Erro de banco de dados: '.htmlspecialchars($e->getMessage()).'</div>';
    exit();
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-qrcode me-2"></i>
                Presença QR
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="view_class.php?turma_id=<?php echo $turma_id; ?>">
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
        <!-- Class Header -->
        <div class="class-header fade-in">
            <h1 class="welcome-title">
                <i class="fas fa-users-cog me-2 text-primary"></i>
                Gerenciar Alunos
            </h1>
            <p class="welcome-subtitle">
                Turma: <?php echo htmlspecialchars($class['nome_turma']); ?> | 
                <?php echo htmlspecialchars($class['disciplina_name']); ?> | 
                Ano <?php echo date('Y', strtotime($class['year'])); ?>
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
                                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                                </div>
                                                <div class="student-details">
                                                    <div><i class="fas fa-id-card me-2"></i> <?php echo htmlspecialchars($student['matricula']); ?></div>
                                                    <div><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($student['email']); ?></div>
                                                </div>
                                            </div>
                                            <div class="student-actions">
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja remover este aluno da turma?');">
                                                    <input type="hidden" name="action" value="remove_student">
                                                    <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($student['aluno_id']); ?>">
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