<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '113615401');
define('DB_NAME', 'judge');
define('INSTALLED', 0);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("数据库连接失败: ". $conn->connect_error);
}
?>