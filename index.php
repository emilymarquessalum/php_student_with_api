
<?php
$_SERVER['REQUEST_URI'] = '/php_student_with_api/';
session_start();

if (isset($_SESSION['prof_id'])) {
    // Usuário já está logado
    header("Location: dashboard.php");
    exit();
} else {
    // Redireciona para login
    header("Location: login.php");
    exit();
}
/*
if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['user_type'] === 'teacher') {
    header('Location: teacher/dashboard.php');
    exit();
} else if ($_SESSION['user_type'] === 'student') {
    header('Location: student/dashboard.php');
    exit();
} */

?>
