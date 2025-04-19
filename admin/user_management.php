<?php
// 设置页面标题
$page_title = '用户管理';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 每页显示的用户数量
$limit = 10;

// 获取当前页码
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 处理删除用户请求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    // 检查用户是否为管理员
    $check_stmt = $conn->prepare("SELECT is_admin FROM users WHERE id =?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $user_row = $check_result->fetch_assoc();
    if ($user_row['is_admin']) {
        echo '<div class="alert alert-danger" role="alert">不能删除管理员用户，请先取消其管理员身份。</div>';
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id =?");
        $delete_stmt->bind_param("i", $user_id);
        if ($delete_stmt->execute()) {
            echo '<div class="alert alert-success" role="alert">用户删除成功！</div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">用户删除失败：' . $conn->error . '</div>';
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
}

// 处理设置/取消管理员身份请求
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $user_id = $_GET['toggle_admin'];
    // 检查是否为唯一的管理员
    $count_admin_stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE is_admin = 1");
    $count_admin_stmt->execute();
    $count_admin_result = $count_admin_stmt->get_result();
    $admin_count_row = $count_admin_result->fetch_assoc();
    $admin_count = $admin_count_row['admin_count'];

    $get_user_stmt = $conn->prepare("SELECT is_admin FROM users WHERE id =?");
    $get_user_stmt->bind_param("i", $user_id);
    $get_user_stmt->execute();
    $get_user_result = $get_user_stmt->get_result();
    $user_row = $get_user_result->fetch_assoc();
    $current_is_admin = $user_row['is_admin'];

    if ($admin_count == 1 && $current_is_admin) {
        echo '<div class="alert alert-danger" role="alert">不能取消唯一的管理员身份。</div>';
    } else {
        $new_is_admin = $current_is_admin ? 0 : 1;
        $toggle_stmt = $conn->prepare("UPDATE users SET is_admin =? WHERE id =?");
        $toggle_stmt->bind_param("ii", $new_is_admin, $user_id);
        if ($toggle_stmt->execute()) {
            echo '<div class="alert alert-success" role="alert">管理员身份更新成功！</div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">管理员身份更新失败：' . $conn->error . '</div>';
        }
        $toggle_stmt->close();
    }
    $count_admin_stmt->close();
    $get_user_stmt->close();
}

// 处理重置密码请求
if (isset($_GET['reset_password']) && is_numeric($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    
    // 生成8位随机密码
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $new_password = substr(str_shuffle($chars), 0, 8);
    $hashed_password = md5($new_password);
    
    // 更新密码
    $reset_stmt = $conn->prepare("UPDATE users SET password =? WHERE id =?");
    $reset_stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($reset_stmt->execute()) {
        echo '<script>
            function copyToClipboard(text) {
                // 尝试使用现代Clipboard API
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        console.log("复制成功");
                    }, function(err) {
                        // 如果Clipboard API失败，使用老式方法
                        fallbackCopyText(text);
                    });
                } else {
                    // 浏览器不支持Clipboard API，使用老式方法
                    fallbackCopyText(text);
                }
            }
            
            function fallbackCopyText(text) {
                var textarea = document.createElement("textarea");
                textarea.value = text;
                textarea.style.position = "fixed";  // 防止页面滚动
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    var successful = document.execCommand("copy");
                    if (!successful) {
                        console.error("复制失败");
                    }
                } catch (err) {
                    console.error("复制失败: ", err);
                }
                
                document.body.removeChild(textarea);
            }
            
            var newPassword = "' . $new_password . '";
            copyToClipboard(newPassword);
            alert("密码已重置为: " + newPassword + "\\n已尝试自动复制到剪贴板");
            window.location.href = "user_management.php?page=' . $page . '";
        </script>';
    } else {
        echo '<div class="alert alert-danger" role="alert">密码重置失败：' . $conn->error . '</div>';
    }
    $reset_stmt->close();
}

// 查询用户总数
$total_query = "SELECT COUNT(*) as total FROM users";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $limit);

// 查询当前页的用户信息
$user_query = "SELECT id, username, email, is_admin, last_login, last_ip FROM users LIMIT $offset, $limit";
$user_result = $conn->query($user_query);
?>

    <div class="row">
        <div class="col-md-12">
            <h2>用户管理</h2>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>是否为管理员</th>
                    <th>最后登录时间</th>
                    <th>最后登录 IP</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ($user_result->num_rows > 0) {
                    while ($user_row = $user_result->fetch_assoc()) {
                        $user_id = $user_row['id'];
                        $username = $user_row['username'];
                        $email = $user_row['email'];
                        $is_admin = $user_row['is_admin'] ? '是' : '否';
                        $last_login = $user_row['last_login'] ? $user_row['last_login'] : '无记录';
                        $last_ip = $user_row['last_ip'] ? $user_row['last_ip'] : '无记录';
                        echo '<tr>';
                        echo '<td>' . $user_id . '</td>';
                        echo '<td>' . htmlspecialchars($username) . '</td>';
                        echo '<td>' . htmlspecialchars($email) . '</td>';
                        echo '<td>' . $is_admin . '</td>';
                        echo '<td>' . $last_login . '</td>';
                        echo '<td>' . $last_ip . '</td>';
                        echo '<td>';
                        echo '<a href="?toggle_admin=' . $user_id . '&page=' . $page . '" class="btn btn-warning btn-sm" onclick="return confirm(\'确定要' . ($user_row['is_admin'] ? '取消' : '设置') . '该用户的管理员身份吗？\')">' . ($user_row['is_admin'] ? '取消管理员' : '设置为管理员') . '</a> ';
                        echo '<a href="?reset_password=' . $user_id . '&page=' . $page . '" class="btn btn-secondary btn-sm mx-2" onclick="return confirm(\'确定要重置该用户的密码吗？\')">重置密码</a> ';
                        if (!$user_row['is_admin']) {
                            echo '<a href="?delete=' . $user_id . '&page=' . $page . '" class="btn btn-danger btn-sm" onclick="return confirm(\'确定要删除该用户吗？\')">删除</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7">暂无用户记录。</td></tr>';
                }
                ?>
                </tbody>
            </table>

            <!-- 分页导航 -->
            <nav aria-label="用户列表分页">
                <ul class="pagination">
                    <?php
                    if ($page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '">上一页</a></li>';
                    }
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active_class . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                    }
                    if ($page < $total_pages) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '">下一页</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>

<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含管理后台母版页
include('../includes/admin_base.php');

// 关闭数据库连接
$conn->close();
?>