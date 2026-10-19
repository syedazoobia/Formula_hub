<?php
// logout.php — Session destruction
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

session_start();
$_SESSION['toast'] = ['type' => 'info', 'message' => 'You have logged out successfully.'];
header("Location: login.php");
exit();
?>
