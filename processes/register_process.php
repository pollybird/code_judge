<?php
// 开启会话
session_start();
// 包含数据库连接配置文件
require_once '../includes/config.php';

// 检查请求方法是否为 POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取用户输入的用户名、邮箱、密码和确认密码
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 验证密码是否一致
    if ($password !== $password_confirm) {
        header('Location: ../public/register.php?error=两次输入的密码不一致');
        exit;
    }

    // 验证用户名和邮箱是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE username =? OR email =?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 用户名或邮箱已存在，重定向到注册页并显示错误信息
        header('Location: ../public/register.php?error=用户名或邮箱已被使用');
        exit;
    }

    // 对密码进行 MD5 加密
    $hashed_password = md5($password);

    // 准备 SQL 插入语句，将新用户信息插入到数据库中
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?,?,?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        // 注册成功，重定向到登录页并显示成功信息
        header('Location: ../public/login.php?success=注册成功，请登录');
    } else {
        // 注册失败，重定向到注册页并显示错误信息
        header('Location: ../public/register.php?error=注册失败，请稍后再试');
    }

    // 关闭数据库连接
    $stmt->close();
    $conn->close();
} else {
    // 非 POST 请求，重定向到注册页
    header('Location: ../public/register.php');
    exit;
}