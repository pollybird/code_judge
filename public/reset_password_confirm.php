<?php
require_once '../includes/config.php';

// 设置页面标题
$page_title = '重置密码确认';

ob_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // 验证token
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "链接无效或已过期";
    } else {
        $data = $result->fetch_assoc();
        $user_id = $data['user_id'];
        ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center">重置密码</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <form method="post" action="../processes/reset_password_confirm_process.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">新密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">重置密码</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

$content = ob_get_clean();
include('../includes/base.php');
?>