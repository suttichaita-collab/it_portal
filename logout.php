<?php //หน้าออกจากระบบ
require_once 'db.php';
session_destroy();
header("Location: login.php");
exit();
?>