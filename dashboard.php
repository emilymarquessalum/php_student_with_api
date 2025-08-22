<?php
require 'config.php';
require_once 'includes/auth.php';
require_auth('professor');

$prof_id = $_SESSION['prof_id'];
$username = $_SESSION['username'] ?? '';

// Get professor's classes from database
// Error message for loading classes
$load_error = '';
try {
    $stmt = $pdo->prepare("
        SELECT t.id, t.nome_turma, d.name as disciplina_name, t.year, t.created_at,
               COUNT(DISTINCT ida.aluno_id) as total_students
        FROM turma t
        JOIN disciplina d ON t.disciplina_id = d.id
        LEFT JOIN integrante_da_turma ida ON t.id = ida.turma_id AND ida.tipo = 'aluno'
    JOIN integrante_da_turma ip ON t.id = ip.turma_id AND ip.tipo = 'professor' AND ip.professor_id = ?
        GROUP BY t.id, t.nome_turma, d.name, t.year, t.created_at
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$prof_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_classes = count($classes);
    $total_students = 0;
    foreach ($classes as $class) {
        $total_students += $class['total_students'];
    }

    // Get classes happening today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as classes_today
        FROM dia_de_aula da
        JOIN integrante_da_turma i ON da.turma_id = i.turma_id
    WHERE i.professor_id = ? AND i.tipo = 'professor'
        AND DATE(da.data) = CURRENT_DATE
    ");
    $stmt->execute([$prof_id]);
    $result = $stmt->fetch();
    $classes_today = $result['classes_today'] ?? 0;
} catch (PDOException $e) {
    $classes = [];
    $total_classes = 0;
    $total_students = 0;
    $classes_today = 0;
    $load_error = 'Erro ao carregar turmas. Tente novamente mais tarde.';
    $load_error_details = $e->getMessage();
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professor - Sistema de Presença QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-qrcode me-2"></i>  
                Presença QR
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#" onclick="toggleProfile()">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="dashboard-header fade-in">
            <h1 class="welcome-title">
                <i class="fas fa-sun me-2 text-primary"></i>
                Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </h1>
            <p class="welcome-subtitle">
                Gerencie suas turmas e controle a presença dos alunos
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo $total_classes; ?></div>
                <div class="stat-label">Turmas Ativas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total de Alunos</div>
            </div>
 
        </div>

        <!-- Main Content - Single Column for Classes -->
        <div class="row">
            <div class="col-12">
                <!-- Classes Table --> 
                <div class="classes-card fade-in">
                    <div class="card-header-modern">
                        <h3 class="card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Minhas Turmas
                        </h3>
                        <div class="card-actions">
                            <a href="registrar_turma.php" class="btn-add-class">
                                <i class="fas fa-plus me-2"></i>
                                Criar Turma
                            </a>
                        </div>
                    </div>
                    <div class="card-body-modern">
                        <?php if ($load_error): ?>
                            <div class="alert alert-danger text-center my-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($load_error); ?>
                                <?php if (!empty($load_error_details)): ?>
                                    <div class="mt-2 small text-danger">
                                        <strong>Detalhes técnicos:</strong><br>
                                        <code><?php echo htmlspecialchars($load_error_details); ?></code>
                                    </div> 
                                <?php endif; ?>
                            </div>
                        <?php elseif (empty($classes)): ?>
                            <div class="text-center py-5"> 
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma turma encontrada</h5>
                                <p class="text-muted mb-4">Você ainda não possui turmas cadastradas.</p>
                                <a href="registrar_turma.php" class="btn-gradient">
                                    <i class="fas fa-plus me-2"></i>
                                    Criar Sua Primeira Turma
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Turma</th>
                                            <th>Disciplina</th>
                                            <th>Ano</th>
                                            <th>Alunos</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead> 
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <div class="class-name"><?php echo htmlspecialchars($class['nome_turma']); ?></div>
                                                <div class="class-details">ID: <?php echo htmlspecialchars($class['id']); ?></div>
                                            </td>
                                            <td>
                                                <span class="class-schedule">
                                                    <i class="fas fa-book me-1"></i>
                                                    <?php echo htmlspecialchars($class['disciplina_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="class-schedule">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('Y', strtotime($class['year'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="student-count">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $class['total_students']; ?> aluno<?php echo $class['total_students'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-circle"></i>
                                                    Ativa
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-view" onclick="viewClass('<?php echo $class['id']; ?>')" 
                                                            title="Ver detalhes da turma">
                                                        <i class="fas fa-eye"></i>
                                                        Ver
                                                    </button>
                                                    <button class="btn-generate" onclick="registerClassDay('<?php echo $class['id']; ?>')"
                                                            title="Registrar nova aula">
                                                        <i class="fas fa-calendar-plus"></i>
                                                        Nova Aula
                                                    </button>
                                                    <button class="btn-generate" onclick="manageStudents('<?php echo $class['id']; ?>')"
                                                            title="Gerenciar alunos">
                                                        <i class="fas fa-users-cog"></i>
                                                        Alunos
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        
         
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function registerClassDay(classId) {
            // Navigate to the class day registration page
            window.location.href = `registrar_aula.php?turma_id=${classId}`;
        }

        function viewClass(classId) {
            // Navigate to class details page
            window.location.href = `view_class.php?turma_id=${classId}`; 
        }

        function manageStudents(classId) {
            // Navigate to student management page
            window.location.href = `manage_students.php?turma_id=${classId}`;
        }

        function showTodayClasses() {
            // Show today's classes
            console.log('Showing today\'s classes');
            // You can implement a modal or navigate to a specific page
        }

        function showAttendanceReport() {
            // Show attendance report
            console.log('Showing attendance report');
            // You can implement a modal or navigate to a specific page
        }

        function showSettings() {
            // Show settings
            console.log('Showing settings');
            // You can implement a modal or navigate to a specific page
        }

        function toggleProfile() {
            // Placeholder for profile dropdown
            console.log('Profile clicked');
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

        // Add some loading animation
        window.addEventListener('load', function() {
            // Add smooth reveal animations
            document.querySelectorAll('.stat-card').forEach((card, index) => {
                setTimeout(() => {
                    card.style.animation = 'slideInUp 0.6s ease forwards';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
