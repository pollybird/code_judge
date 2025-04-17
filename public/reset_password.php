<?php
// 如果用户已经登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 设置页面标题
$page_title = '重置密码';

// 开启输出缓冲
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="text-center">重置密码</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                重置链接已发送到您的邮箱，请查收（有效期30分钟）
            </div>
        <?php endif; ?>
        <form method="post" action="../processes/reset_password_process.php">
            <div class="mb-3">
                <label for="email" class="form-label">注册邮箱</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">发送重置邮件</button>
        </form>
        <p class="mt-3 text-center">记得使用注册时填写的邮箱。</p>
    </div>
</div>

<?php
$content = ob_get_clean();
include('../includes/base.php');
?>