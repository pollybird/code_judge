<?php
// 启动会话
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = '登录';
ob_start();
?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">登录</h2>
            <form method="post" action="../processes/login_process.php">
                <div class="mb-3">
                    <label for="username" class="form-label">用户名</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">登录</button>
                <a href="reset_password.php" class="btn btn-link">忘记密码？</a>
            </form>
        </div>
    </div>
<?php
$content = ob_get_clean();
include('../includes/base.php');
?>