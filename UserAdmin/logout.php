<?php
session_start();    // ここにもこれが必要だった
unset($_SESSION['login_with']); // ログイン状況を格納している部分を削除
header("Location:../index.php");
exit();
?>