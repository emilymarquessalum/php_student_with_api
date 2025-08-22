<?php
require 'config.php';
require_once 'includes/auth.php';
require_auth('professor');
$prof_id = $_SESSION['prof_id'];

// Get dia_aula_id from URL
$dia_aula_id = $_GET['dia_aula_id'] ?? null;
if (!$dia_aula_id) {
    header('Location: dashboard.php');
    exit();
}

// Get class day information and verify professor has access
try {
    $stmt = $pdo->prepare("
        SELECT da.*, t.nome_turma, d.name as disciplina_name
        FROM dia_de_aula da
        JOIN turma t ON da.turma_id = t.id
        JOIN disciplina d ON t.disciplina_id = d.id 
        JOIN integrante_da_turma i ON t.id = i.turma_id
        WHERE da.id = ? AND i.professor_id = ? AND i.tipo = 'professor'
    ");
    $stmt->execute([$dia_aula_id, $prof_id]);
    $class_day = $stmt->fetch();

    if (!$class_day) {
        echo '<div style="color:red; font-weight:bold;">Erro: Você não tem acesso a esta aula ou ela não existe.<br>dia_aula_id: ' . htmlspecialchars($dia_aula_id) . '<br>prof_id: ' . htmlspecialchars($prof_id) . '</div>';
        exit();
    }

    // Get attendance count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_presenca
        FROM presenca 
        WHERE dia_aula_id = ?
    ");
    $stmt->execute([$dia_aula_id]);
    $attendance_count = $stmt->fetch()['total_presenca'];
} catch (PDOException $e) {
    echo '<div style="color:red; font-weight:bold;">Erro de banco de dados: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}

// Handle AJAX requests for attendance updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_attendance') {
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_presenca
            FROM presenca 
            WHERE dia_aula_id = ?
        ");
        $stmt->execute([$dia_aula_id]);
        $count = $stmt->fetch()['total_presenca'];

        echo json_encode(['success' => true, 'count' => $count]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Generate QR Code data as a URL to the student attendance page
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$student_url = rtrim($base_url, '/\\') . "/student/record_attendance.php?dia_aula_id=" . urlencode($dia_aula_id);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Presença - <?php echo htmlspecialchars($class_day['nome_turma']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/qr-page.css" rel="stylesheet">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
</head>

<body class="qr-page">
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

    <div class="container-fluid p-0">
        <!-- Main QR Code Section - Full Width -->
        <div class="qr-main-section">
            <div class="qr-content">


                <!-- QR Code Container -->
                <div class="qr-display-container qr-code-active fade-in">
                    <div class="qr-code-wrapper">
                        <div id="qrcode" class="qr-code-element"></div>
                    </div>
                    <div class="qr-instructions">
                        <h3>
                            <i class="fas fa-mobile-alt"></i>
                            Escaneie o QR CODE para marcar presença
                        </h3>
                    </div>
                </div>
                <!-- Combined Class Info and Attendance Card -->
                <div class="class-attendance-card fade-in">
                    <div class="card-content">
                        <!-- Left side - Class Info -->
                        <div class="class-info-section">
                            <h1 class="class-title">
                                <?php echo htmlspecialchars($class_day['nome_turma']); ?>
                            </h1>
                            <p class="class-subtitle">
                                <?php echo htmlspecialchars($class_day['disciplina_name']); ?> •
                                <?php echo date('d/m/Y H:i', strtotime($class_day['data'])); ?>
                            <div class="counter-label">Alunos Presentes</div>
                        </div>

                        <!-- Status items to the right of attendance -->
                        <div class="status-items">
                            <div class="stat-item">
                                <i class="fas fa-clock text-primary"></i>
                                <span>Aula às <?php echo date('H:i', strtotime($class_day['data'])); ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-calendar text-success"></i>
                                <span><?php echo date('d/m/Y', strtotime($class_day['data'])); ?></span>
                            </div>
                            <div class="stat-item" id="statusItem">
                                <i class="fas fa-circle text-success"></i>
                                <span>QR Code Ativo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Action Buttons -->
        <div class="qr-actions fade-in">
            <button class="btn-regenerate" onclick="regenerateQR()">
                <i class="fas fa-sync-alt"></i>
                Regenerar QR Code
            </button>

        </div>

        <!-- Stats Row - Below QR Code -->
        <div class="stats-row">
            <!-- Recent Activity -->
            <div class="stats-card fade-in">
                <div class="stats-header">
                    <h5>
                        <i class="fas fa-history"></i>
                        Atividade Recente
                    </h5>
                </div>
                <div class="stats-body">
                    <div id="recentActivity" class="activity-list">
                        <div class="activity-item">
                            <i class="fas fa-info-circle text-muted"></i>
                            <span>QR Code gerado com sucesso</span>
                            <small><?php echo date('H:i'); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="stats-card fade-in">
                <div class="stats-header">
                    <h5>
                        <i class="fas fa-lightbulb"></i>
                        Instruções
                    </h5>
                </div>
                <div class="stats-body">
                    <ul class="instruction-list">
                        <li>Mantenha esta página aberta durante a aula</li>
                        <li>Os alunos devem escanear o QR Code</li>
                        <li>A contagem será atualizada automaticamente</li>
                        <li>Clique em "Finalizar Aula" ao terminar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let qrCode = null;
        let attendanceInterval = null;

        // Generate initial QR Code - BIGGER SIZE
        function generateQRCode() {
            const qrData = <?php echo json_encode($student_url); ?>;
            // Clear previous QR code
            document.getElementById('qrcode').innerHTML = '';
            // Generate new QR code with BIGGER SIZE
            qrCode = new QRCode(document.getElementById('qrcode'), {
                text: qrData,
                width: 350, // Increased from 300
                height: 350, // Increased from 300
                colorDark: "#4f46e5",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        // Regenerate QR Code - BIGGER SIZE
        function regenerateQR() {
            const qrData = <?php echo json_encode($student_url); ?>;
            document.getElementById('qrcode').innerHTML = '';
            qrCode = new QRCode(document.getElementById('qrcode'), {
                text: qrData,
                width: 350, // Increased from 300
                height: 350, // Increased from 300
                colorDark: "#4f46e5",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            addActivity('QR Code regenerado', 'fas fa-sync-alt text-primary');
        }

        // Update attendance count
        function updateAttendanceCount() {
            fetch('qr_presenca.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_attendance'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const currentCount = parseInt(document.getElementById('attendanceCount').textContent);
                        const newCount = data.count;

                        if (newCount > currentCount) {
                            document.getElementById('attendanceCount').textContent = newCount;
                            document.getElementById('attendanceCount').style.animation = 'bounce 0.5s ease';
                            setTimeout(() => {
                                document.getElementById('attendanceCount').style.animation = '';
                            }, 500);

                            addActivity(`Aluno marcou presença (${newCount} total)`, 'fas fa-user-check text-success');
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Add activity to recent list
        function addActivity(message, iconClass) {
            const activityList = document.getElementById('recentActivity');
            const time = new Date().toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            activityItem.innerHTML = `
                <i class="${iconClass}"></i>
                <span>${message}</span>
                <small>${time}</small>
            `;

            activityList.insertBefore(activityItem, activityList.firstChild);

            // Keep only last 5 activities
            while (activityList.children.length > 5) {
                activityList.removeChild(activityList.lastChild);
            }
        }



        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Generate QR Code
            generateQRCode();

            // Start polling for attendance updates every 5 seconds
            attendanceInterval = setInterval(updateAttendanceCount, 5000);

            // Add fade-in animations
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
                }, index * 200);
            });
        });
    </script>
</body>

</html>