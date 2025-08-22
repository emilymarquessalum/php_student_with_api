<?php
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['prof_id']);
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit();
    }
}
?>
