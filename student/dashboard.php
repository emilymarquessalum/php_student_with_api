<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config.php'; 
require_once '../includes/db.php';
require_once '../includes/functions.php'; 

require_auth('student');
// Debug: show session and login data before auth check
if (isset($_GET['debug'])) {
    echo '<pre style="background:#eee;padding:10px;">SESSION (before require_auth): ' . print_r($_SESSION, true) . "\n";
    echo '_POST: '; var_dump($_POST);
    echo '_GET: '; var_dump($_GET);
    echo '</pre>';
} 
// Debug: show session after auth check
if (isset($_GET['debug'])) { 
    echo '<pre style="background:#eee;padding:10px;">SESSION (after require_auth): ' . print_r($_SESSION, true) . '</pre>';
}
?> 

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
        function onScanSuccess(decodedText, decodedResult) {
            try {
                const attendanceData = JSON.parse(decodedText);
                const currentTime = Date.now();
                const scanTimeDiff = currentTime - attendanceData.timestamp;
                
                // Verifica se o QR code não está expirado (5 minutos de validade)
                if (scanTimeDiff > 300000) { // 5 minutos em milissegundos
                    document.getElementById('result').innerHTML = 
                        '<div class="alert alert-danger">QR Code expirado. Por favor, solicite um novo código.</div>';
                    return;
                }

                // Envia dados de presença para o servidor
                fetch('record_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        classId: attendanceData.classId,
                        timestamp: attendanceData.timestamp,
                        token: attendanceData.token
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('result').innerHTML = 
                        `<div class="alert alert-success">Presença registrada com sucesso!</div>`;
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = 
                        `<div class="alert alert-danger">Erro ao registrar presença. Por favor, tente novamente.</div>`;
                });

            } catch (error) {
                document.getElementById('result').innerHTML = 
                    '<div class="alert alert-danger">QR Code inválido. Por favor, tente novamente.</div>';
            }
        }

        function onScanFailure(error) {
            // Trata falha na leitura, geralmente melhor ignorar e continuar escaneando
            console.warn(`Falha na leitura do QR Code = ${error}`);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: {width: 250, height: 250} },
            /* verbose= */ false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>