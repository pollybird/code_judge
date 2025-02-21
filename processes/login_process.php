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
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // 对输入的密码进行 MD5 加密，与数据库中的密码进行比较
        $hashed_password = md5($password);
        if ($hashed_password === $row['password']) {
            // 密码匹配，设置会话变量
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['is_admin'] = $row['is_admin'];

            // 获取用户的 IP 地址
            $user_ip = $_SERVER['REMOTE_ADDR'];
            // 获取当前时间
            $current_time = date('Y-m-d H:i:s');

            // 更新用户的最后登录时间和 IP 地址
            $update_stmt = $conn->prepare("UPDATE users SET last_login =?, last_ip =? WHERE id =?");
            $update_stmt->bind_param("ssi", $current_time, $user_ip, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // 登录成功，重定向到首页
            header('Location: ../public/index.php');
            exit;
        } else {
            // 密码不匹配，重定向到登录页并显示错误信息
            header('Location: ../public/login.php?error=密码错误');
            exit;
        }
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