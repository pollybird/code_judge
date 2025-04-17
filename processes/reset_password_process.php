<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // 验证邮箱是否存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../public/reset_password.php?error=该邮箱未注册');
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // 生成唯一token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 1800); // 30分钟后过期
    
    // 保存token到数据库
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $token, $expires);
    $stmt->execute();
    
    // 发送邮件
    $stmt = $conn->prepare("SELECT value FROM site_config WHERE name = 'site_domain'");
    $stmt->execute();
    $domain_result = $stmt->get_result();
    $domain = $domain_result->fetch_assoc()['value'] ?? 'yourdomain.com';
    
    $reset_link = "http://{$domain}/public/reset_password_confirm.php?token=$token";
    $subject = "密码重置请求";
    $message = "请点击以下链接重置密码（30分钟内有效）:\n\n$reset_link";
    $headers = "From: no-reply@yourdomain.com";
    
    if (mail($email, $subject, $message, $headers)) {
        header('Location: ../public/reset_password.php?success=1');
    } else {
        header('Location: ../public/reset_password.php?error=邮件发送失败，请稍后再试');
    }
    exit;
}
?>