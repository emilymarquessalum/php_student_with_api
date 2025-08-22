<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'professor') {
        header('Location: dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] === 'student') {
        header('Location: student/dashboard.php');
        exit();
    }
}


require 'config.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Check if user exists in usuario
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if professor
        $stmt = $pdo->prepare("SELECT * FROM professor WHERE email = ?");
        $stmt->execute([$email]);
        $professor = $stmt->fetch();
        if ($professor) {
            $_SESSION['user_type'] = 'professor';
            $_SESSION['prof_id'] = $professor['id'];
            $_SESSION['username'] = $professor['name'] ?? $email;
            $_SESSION['email'] = $professor['email'];
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
            $_SESSION['token_expiry'] = time() + 1800;
            // Redirect to intended page if set
            if (!empty($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        }
        // Check if student
        $stmt = $pdo->prepare("SELECT * FROM aluno WHERE email = ?");
        $stmt->execute([$email]);
        $aluno = $stmt->fetch();
        if ($aluno) {
            $_SESSION['user_type'] = 'student';
            $_SESSION['aluno_id'] = $aluno['id'];
            $_SESSION['username'] = $aluno['name'] ?? $email;
            $_SESSION['matricula'] = $aluno['matricula'] ?? '';
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
            $_SESSION['token_expiry'] = time() + 1800;
            // Redirect to intended page if set
            if (!empty($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            } else {
                header("Location: student/dashboard.php");
            }
            exit();
        }
        // User exists in usuario but not in professor or aluno 
        $erro = "Seu usuário existe, mas não está cadastrado como professor ou aluno. Fale com o administrador.";
    } else {
        $erro = "Email ou senha incorretos";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Presença QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/login.css" rel="stylesheet">
</head>

<body class="page-layout gradient">
    <div class="container flex items-center justify-center" style="min-height: 100vh;">
        <div class="login-container">
            <div class="login-content">
                <!-- Visual Section -->
                <div class="login-visual-section">
                    <div class="visual-content">
                        <div class="visual-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h1 class="visual-title">Presença QR</h1>
                        <p class="visual-subtitle">Sistema moderno de controle de presença com QR Code</p>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="login-form-section">
                    <div class="login-header">
                        <h2 class="login-title">Bem-vindo de volta!</h2>
                        <p class="login-subtitle">Faça login para acessar o sistema</p>
                    </div>

                    <form method="POST" autocomplete="off">
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($erro); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </label>
                            <input type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="seu@email.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="senha" class="form-label">
                                <i class="fas fa-lock"></i>
                                Senha
                            </label>
                            <input type="password"
                                id="senha"
                                name="senha"
                                class="form-control"
                                placeholder="••••••••"
                                required>
                        </div>

                        <button type="submit" class="btn btn-gradient btn-full">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Entrar no Sistema
                        </button>
                    </form>

                    <div class="test-accounts">
                        <h5 class="text-center mb-md">
                            <i class="fas fa-users me-2"></i>
                            Contas de Teste
                        </h5>

                        <div class="account-card" onclick="fillLogin('professor@escola.com', '123456')">
                            <div class="flex items-center gap-md">
                                <i class="fas fa-chalkboard-teacher text-primary"></i>
                                <div>
                                    <div class="font-semibold">Professor</div>
                                    <div class="text-sm text-muted">professor@escola.com</div>
                                    <div class="text-sm text-muted">Senha: 123456</div>
                                </div>
                            </div>
                        </div>

                        <div class="account-card" onclick="fillLogin('aluno1@escola.com', '123456')">
                            <div class="flex items-center gap-md">
                                <i class="fas fa-user-graduate text-success"></i>
                                <div>
                                    <div class="font-semibold">Aluno</div>
                                    <div class="text-sm text-muted">aluno1@escola.com</div>
                                    <div class="text-sm text-muted">Senha: 123456</div>
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
        function fillLogin(email, password) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="senha"]').value = password;

            // Add visual feedback
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="senha"]');

            emailInput.classList.add('is-valid');
            passwordInput.classList.add('is-valid');

            // Focus the submit button
            document.querySelector('button[type="submit"]').focus();
        }

        // Form validation and visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required]');

            // Real-time validation
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });

                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('is-invalid');
                    }
                });
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showAlert('Por favor, preencha todos os campos.', 'warning');
                }
            });
        });

        function showAlert(message, type) {
            const existing = document.querySelector('.alert-temp');
            if (existing) existing.remove();

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-temp`;
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            `;

            const form = document.querySelector('form');
            form.insertBefore(alertDiv, form.firstChild);

            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>

</html>