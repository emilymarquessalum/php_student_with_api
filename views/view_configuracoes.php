
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_auth('professor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nome'], $_POST['login'], $_POST['email'], $_POST['senha'])) {
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $email = $_POST['email'];
        $prof_id = $_SESSION['prof_id'];
        $senha = $_POST['senha'];

        // Buscar a senha atual do usuário no banco
        $stmtSenha = $pdo->prepare("SELECT senha FROM config_prof WHERE id = ?");
        $stmtSenha->execute([$prof_id]);
        $rowSenha = $stmtSenha->fetch(PDO::FETCH_ASSOC);

        // Verifica a senha diretamente (sem hash)
        if ($rowSenha && isset($rowSenha['senha'])) {
            $senhaBanco = $rowSenha['senha'];
            if ($senha === $senhaBanco) {
                try {
                    $stmt = $pdo->prepare("UPDATE config_prof SET nome = ?, login = ?, email = ? WHERE id = ?");
                    $result = $stmt->execute([$nome, $login, $email, $prof_id]);
                    if ($result) {
                        header("Location: view_configuracoes.php");
                        exit;
                    } else {
                        $msg = "Falha ao atualizar os dados.";
                    }
                } catch (PDOException $e) {
                    $msg = "Erro ao atualizar: " . $e->getMessage();
                }
            } else {
                $msg = "Senha atual incorreta.";
            }
        } else {
            $msg = "Senha atual incorreta.";
        }
    } else {
        $msg = "Por favor, preencha todos os campos obrigatórios.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM config_prof WHERE id = ?");
$stmt->execute([$_SESSION['prof_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($user)) {
    if (isset($_GET['editar'])) {
?>
        <form method="post" autocomplete="off">
            <p><strong>Nome:</strong> <input type="text" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required></p>
            <p><strong>Login:</strong> <input type="text" name="login" value="<?= htmlspecialchars($user['login']) ?>" required></p>
            <p><strong>Email:</strong> <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required></p>
            <p><strong>Confirme sua senha atual:</strong> <input type="password" name="senha" required></p>
            <button type="submit">Salvar</button>
            <?php if (isset($msg)) : ?>
            <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
            <?php endif; ?>
            <a href="view_configuracoes.php">Cancelar</a>
        </form>
    <?php
    } else {
    ?>
        <p><strong>Nome:</strong> <?= htmlspecialchars($user['nome']) ?></p>
        <p><strong>Login:</strong> <?= htmlspecialchars($user['login']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'Não definido') ?></p>
        <a href="view_configuracoes.php?editar=1">Editar Configurações</a>
<?php
    }
}
?>