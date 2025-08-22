<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config.php';
require_once '../includes/functions.php';

require_auth('aluno');

// The main PHP code is now minimal, as all logic is handled by the API
$student_id = $_SESSION['aluno_id'] ?? null;
$access_token = $_SESSION['access_token'] ?? '';
$api_endpoint = $_ENV['API_ENDPOINT'] ?? 'http://localhost:8000'; // Get API endpoint from config
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Painel do Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Painel do Aluno</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <div class="row mt-4">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3>Escanear QR Code de Presença</h3>
                    </div>
                    <div class="card-body">
                        <div id="reader"></div>
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentId = <?php echo json_encode($student_id); ?>;
        const accessToken = <?php echo json_encode($access_token); ?>;
        const apiEndpoint = <?php echo json_encode($api_endpoint); ?>;

        function onScanSuccess(decodedText, decodedResult) {
            try {
                // The QR code data should now be a simple dia_aula_id or a token
                const diaAulaId = decodedText;

                // Construct the full API URL for recording attendance
                const apiUrl = `${apiEndpoint}/attendance/record`;

                // Send attendance data directly to the API
                fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${accessToken}`, // Use the access token
                        },
                        body: JSON.stringify({
                            aluno_id: studentId,
                            dia_aula_id: diaAulaId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('result').innerHTML =
                                `<div class="alert alert-success">Presença registrada com sucesso!</div>`;
                        } else {
                            document.getElementById('result').innerHTML =
                                `<div class="alert alert-danger">${data.message || 'Erro ao registrar presença.'}</div>`;
                        }
                    })
                    .catch(error => {
                        document.getElementById('result').innerHTML =
                            `<div class="alert alert-danger">Erro de conexão com o servidor. Por favor, tente novamente.</div>`;
                        console.error('API Error:', error);
                    });

            } catch (error) {
                document.getElementById('result').innerHTML =
                    `<div class="alert alert-danger">QR Code inválido. Por favor, tente novamente.</div>`;
            }
        }

        function onScanFailure(error) {
            // It's generally best to ignore these errors unless it's a critical issue
            console.warn(`Falha na leitura do QR Code = ${error}`);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                }
            },
            /* verbose= */
            false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>