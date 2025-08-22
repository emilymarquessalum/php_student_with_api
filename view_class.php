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

try {
    // Get class information and verify professor has access 

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
        echo '<div style="color:red; font-weight:bold;">Erro: Você não tem acesso a esta turma ou ela não existe.<br>turma_id: ' . htmlspecialchars($turma_id) . '<br>prof_id: ' . htmlspecialchars($prof_id) . '</div>';
        exit();
    }

    // Get class days with attendance counts
    $stmt = $pdo->prepare("
        SELECT 
            da.*,
            COUNT(p.id) as total_presenca
        FROM dia_de_aula da
        LEFT JOIN presenca p ON da.id = p.dia_aula_id
        WHERE da.turma_id = ? 
        GROUP BY da.id
        ORDER BY da.data DESC
    ");
    $stmt->execute([$turma_id]);
    $class_days = $stmt->fetchAll();

    // Get students in this class
    $stmt = $pdo->prepare("
        SELECT 
            i.aluno_id,
            i.aluno_id as student_name
        FROM integrante_da_turma i
        WHERE i.turma_id = ? AND i.tipo = 'aluno'
        ORDER BY i.aluno_id
    ");
    $stmt->execute([$turma_id]);
    $students = $stmt->fetchAll();

    // Calculate statistics
    $total_classes = count($class_days);
    $total_students = count($students);
    $classes_with_attendance = array_filter($class_days, function ($day) {
        return $day['total_presenca'] > 0;
    });
    $avg_attendance = $total_classes > 0 ?
        array_sum(array_column($class_days, 'total_presenca')) / $total_classes : 0;
} catch (PDOException $e) {
    echo '<div style="color:red; font-weight:bold;">Erro de banco de dados: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['nome_turma']); ?> - Detalhes da Turma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/class-view.css" rel="stylesheet">
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

    <div class="container mt-4">
        <!-- Class Header -->
        <div class="class-header fade-in">
            <div class="class-header-content">
                <div class="class-info">
                    <h1 class="class-title">
                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                        <?php echo htmlspecialchars($class['nome_turma']); ?>
                    </h1>
                    <p class="class-subtitle">
                        <?php echo htmlspecialchars($class['disciplina_name']); ?> •
                        Ano <?php echo date('Y', strtotime($class['year'])); ?>
                        <!-- Statistics Grid -->
                    <div class="stats-grid fade-in">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $total_classes; ?></div>
                                <div class="stat-label">Aulas Registradas</div>
                            </div>
                        </div>

                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $total_students; ?></div>
                                <div class="stat-label">Alunos na Turma</div>
                            </div>
                        </div>

                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo number_format($avg_attendance, 1); ?></div>
                                <div class="stat-label">Média de Presença</div>
                            </div>
                        </div>

                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">
                                    <?php echo $total_students > 0 ? number_format(($avg_attendance / $total_students) * 100, 1) : 0; ?>%
                                </div>
                                <div class="stat-label">Taxa de Presença</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Days History -->
                        <div class="col-lg-8">
                            <div class="content-card fade-in">
                                <div class="card-header-modern">
                                    <h3 class="card-title">
                                        <i class="fas fa-history me-2"></i>
                                        Histórico de Aulas
                                    </h3>
                                    <div class="card-actions">
                                        <button class="btn-outline" onclick="exportAttendance()">
                                            <i class="fas fa-download me-1"></i>
                                            Exportar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body-modern">
                                    <?php if (empty($class_days)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhuma aula registrada</h5>
                                            <p class="text-muted mb-4">
                                                Comece registrando sua primeira aula para esta turma.
                                            </p>
                                            <a href="registrar_aula.php?turma_id=<?php echo $class['id']; ?>" class="btn-gradient">
                                                <i class="fas fa-plus me-2"></i>
                                                Registrar Primeira Aula
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="class-days-list">
                                            <?php foreach ($class_days as $index => $day): ?>
                                                <div class="class-day-item <?php echo $index < 3 ? 'recent' : ''; ?>">
                                                    <div class="day-date">
                                                        <div class="date-main">
                                                            <?php echo date('d/m', strtotime($day['data'])); ?>
                                                        </div>
                                                        <div class="date-year">
                                                            <?php echo date('Y', strtotime($day['data'])); ?>
                                                        </div>
                                                        <div class="date-time">
                                                            <?php echo date('H:i', strtotime($day['data'])); ?>
                                                        </div>
                                                    </div>

                                                    <div class="day-info">
                                                        <div class="day-status">
                                                            <?php if ($day['aula_foi_dada']): ?>
                                                                <span class="status-badge status-completed">
                                                                    <i class="fas fa-check-circle"></i>
                                                                    Aula Realizada
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-pending">
                                                                    <i class="fas fa-clock"></i>
                                                                    Aguardando
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="day-attendance">
                                                            <i class="fas fa-users me-1"></i>
                                                            <strong><?php echo $day['total_presenca']; ?></strong> alunos presentes
                                                        </div>
                                                    </div>

                                                    <div class="day-actions">
                                                        <?php if (!$day['aula_foi_dada']): ?>
                                                            <a href="qr_presenca.php?dia_aula_id=<?php echo $day['id']; ?>"
                                                                class="btn-action primary">
                                                                <i class="fas fa-qrcode"></i>
                                                                QR Code
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Students List -->
                        <div class="col-lg-4">
                            <div class="content-card fade-in">
                                <div class="card-header-modern">
                                    <h3 class="card-title">
                                        <i class="fas fa-users me-2"></i>
                                        Alunos da Turma
                                    </h3>
                                </div>
                                <div class="card-body-modern">
                                    <?php if (empty($students)): ?>
                                        <div class="empty-state-small">
                                            <i class="fas fa-user-plus fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">Nenhum aluno cadastrado</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="students-list">
                                            <?php foreach ($students as $student): ?>
                                                <div class="student-item">
                                                    <div class="student-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="student-info">
                                                        <div class="student-name">
                                                            <?php echo htmlspecialchars($student['student_name']); ?>
                                                        </div>
                                                        <div class="student-id">
                                                            ID: <?php echo htmlspecialchars($student['aluno_id']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="student-stats">
                                                        <button class="btn-stats"
                                                            onclick="viewStudentStats('<?php echo $student['aluno_id']; ?>')">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Quick Actions Card -->
                            <div class="content-card fade-in">
                                <div class="card-header-modern">
                                    <h3 class="card-title">
                                        <i class="fas fa-bolt me-2"></i>
                                        Ações Rápidas
                                    </h3>
                                </div>
                                <div class="card-body-modern">
                                    <div class="quick-actions">
                                        <a href="registrar_aula.php?turma_id=<?php echo $class['id']; ?>"
                                            class="quick-action-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>Nova Aula</span>
                                        </a>
                                        <button class="quick-action-item" onclick="generateReport()">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Relatório</span>
                                        </button>
                                        <button class="quick-action-item" onclick="exportAttendance()">
                                            <i class="fas fa-download"></i>
                                            <span>Exportar</span>
                                        </button>
                                        <button class="quick-action-item" onclick="classSettings()">
                                            <i class="fas fa-cog"></i>
                                            <span>Configurar</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                    function viewAttendanceDetails(dayId) {
                        // TODO: Implement attendance details modal or page
                        console.log('Viewing attendance for day:', dayId);
                    }

                    function viewStudentStats(studentId) {
                        // TODO: Implement student statistics modal
                        console.log('Viewing stats for student:', studentId);
                    }

                    function exportAttendance() {
                        // TODO: Implement attendance export functionality
                        console.log('Exporting attendance data');
                    }

                    function generateReport() {
                        // TODO: Implement report generation
                        console.log('Generating report');
                    }

                    function classSettings() {
                        // TODO: Implement class settings
                        console.log('Opening class settings');
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