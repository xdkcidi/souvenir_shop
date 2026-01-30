<?php
session_start();

setcookie('id', '', time() - 3600, '/');
setcookie('role', '', time() - 3600, '/');

session_unset();
session_destroy();

header('Location: ../index.php');
exit;
