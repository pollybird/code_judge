<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 验证密码是否匹配
    if ($password !== $confirm_password) {
        header('Location: ../public/reset_password_confirm.php?token='.$token.'&error=两次输入的密码不一致');
        exit;
    }
    
    // 验证token
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../public/reset_password_confirm.php?token='.$token.'&error=链接无效或已过期');
        exit;
    }
    
    $data = $result->fetch_assoc();
    $user_id = $data['user_id'];
    
    // 更新密码
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
    
    // 删除已使用的token
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    header('Location: ../public/login.php?success=密码重置成功，请登录');
    exit;
}
?>