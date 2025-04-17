<?php
// 若用户已登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 设置页面标题
$page_title = '用户注册';

// 开启输出缓冲，用于捕获页面内容
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">注册新用户</h2>
            <!-- 若有错误信息，显示错误提示 -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <form method="post" action="../processes/register_process.php">
                <div class="mb-3">
                    <label for="username" class="form-label">用户名</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">邮箱</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">确认密码</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit" class="btn btn-primary">注册</button>
            </form>
            <p class="mt-3 text-center">已有账号？<a href="login.php">立即登录</a></p>
        </div>
    </div>

<?php
// 获取并清空输出缓冲区的内容，赋值给 $content 变量
$content = ob_get_clean();
// 包含母版页
include('../includes/base.php');
?>