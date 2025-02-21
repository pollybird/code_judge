<?php
// 设置页面标题
$page_title = '更改密码';

// 开启输出缓冲
ob_start();

// 引入数据库连接文件
require_once '../includes/config.php';

// 检查用户是否已登录，这里假设通过 session 来判断用户是否登录
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 处理密码更改表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $oldPassword = $_POST['old_password']?? '';
    $newPassword1 = $_POST['new_password1']?? '';
    $newPassword2 = $_POST['new_password2']?? '';

    $userId = $_SESSION['user_id'];

    // 验证输入
    if (empty($oldPassword) || empty($newPassword1) || empty($newPassword2)) {
        $error = "所有字段都必须填写";
    } elseif ($newPassword1!== $newPassword2) {
        $error = "两次输入的新密码不一致";
    } else {
        // 从数据库获取用户当前密码
        $sql = "SELECT password FROM users WHERE id =?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $hashedOldPassword = $row['password'];

            // 验证旧密码（使用MD5加密验证）
            if (md5($oldPassword) === $hashedOldPassword) {
                // 对新密码进行MD5加密
                $hashedNewPassword = md5($newPassword1);

                // 更新数据库中的密码
                $sqlUpdate = "UPDATE users SET password =? WHERE id =?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("si", $hashedNewPassword, $userId);

                if ($stmtUpdate->execute()) {
                    $success = "密码更改成功";
                } else {
                    $error = "密码更改失败：". $conn->error;
                }
                $stmtUpdate->close();
            } else {
                $error = "旧密码错误";
            }
        } else {
            $error = "用户未找到";
        }
        $stmt->close();
    }
}
?>
    <div>
        <h1>更改密码</h1>
        <?php if (isset($error)) :?>
            <div class="alert alert-danger"><?php echo $error;?></div>
        <?php endif;?>
        <?php if (isset($success)) :?>
            <div class="alert alert-success"><?php echo $success;?></div>
        <?php endif;?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
            <div class="mb-3">
                <label for="old_password" class="form-label">旧密码</label>
                <input type="password" class="form-control" id="old_password" name="old_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password1" class="form-label">新密码</label>
                <input type="password" class="form-control" id="new_password1" name="new_password1" required>
            </div>
            <div class="mb-3">
                <label for="new_password2" class="form-label">确认新密码</label>
                <input type="password" class="form-control" id="new_password2" name="new_password2" required>
            </div>
            <button type="submit" class="btn btn-primary">更改密码</button>
        </form>
    </div>
<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含 base.php 母版页
include('../includes/base.php');
?>