<?php
// 开启会话
session_start();
// 包含数据库连接配置文件
require_once '../includes/config.php';

// 检查请求方法是否为 POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取用户输入的用户名和密码
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 准备 SQL 查询语句，用于检查用户名和密码是否匹配
    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username =?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 检查是否有匹配的用户记录
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: ../public/index.php');
        exit;
    } else {
        // 未找到匹配的用户名，重定向到登录页并显示错误信息
        header('Location: ../public/login.php?error=用户名不存在');
        exit;
    }

    // 关闭数据库连接
    $stmt->close();
    $conn->close();
} else {
    // 非 POST 请求，重定向到登录页
    header('Location: ../public/login.php');
    exit;
}