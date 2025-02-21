<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取用户输入的信息
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    $site_name = $_POST['site_name'];
    $site_keywords = $_POST['site_keywords'];
    $site_description = $_POST['site_description'];
    $icp_number = $_POST['icp_number'];
    $jobe_server = $_POST['jobe_server'];
    $admin_username = $_POST['admin_username'];
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];
    $admin_password_confirm = $_POST['admin_password_confirm'];

    // 检查两次输入的管理员密码是否一致
    if ($admin_password !== $admin_password_confirm) {
        die("两次输入的管理员密码不一致，请重新输入。");
    }
    $admin_password = md5($admin_password);

    // 连接到 MySQL 服务器
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("数据库连接失败: ". $conn->connect_error);
    }

    // 根据用户输入的数据库名称创建数据库
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (!$conn->query($create_db_sql)) {
        die("创建数据库出错: ". $conn->error);
    }

    // 选择数据库
    $conn->select_db($db_name);

    // 读取 install.sql 文件内容
    $sql = file_get_contents('../includes/install.sql');
    if ($sql === false) {
        die("无法读取 install.sql 文件");
    }

    // 执行 SQL 脚本创建表
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        if (trim($statement) != '') {
            if (!$conn->query($statement)) {
                die("执行 SQL 语句出错: ". $conn->error);
            }
        }
    }

    // 插入网站配置信息
    $config_items = [
        'site_name' => $site_name,
        'site_keywords' => $site_keywords,
        'site_description' => $site_description,
        'icp_number' => $icp_number,
        'jobe_server' => $jobe_server
    ];
    foreach ($config_items as $name => $value) {
        $stmt = $conn->prepare("INSERT INTO site_config (name, value) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $value);
        if (!$stmt->execute()) {
            die("插入网站配置信息出错: ". $stmt->error);
        }
        $stmt->close();
    }

    // 创建管理员用户
    $is_admin = true;
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $admin_username, $admin_password, $admin_email, $is_admin);
    if (!$stmt->execute()) {
        die("创建管理员用户出错: ". $stmt->error);
    }
    $stmt->close();

    // 生成 config.php 文件
    $config_content = "<?php\n";
    $config_content .= "define('DB_HOST', '$db_host');\n";
    $config_content .= "define('DB_USER', '$db_user');\n";
    $config_content .= "define('DB_PASS', '$db_pass');\n";
    $config_content .= "define('DB_NAME', '$db_name');\n";
    $config_content .= "define('INSTALLED', 1);\n";
    $config_content .= "$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n";
    $config_content .= "if ($conn->connect_error) {\n";
    $config_content .= "    die(\"数据库连接失败: \". $conn->connect_error);\n";
    $config_content .= "}\n";
    $config_content .= "?>";
    file_put_contents('../includes/config.php', $config_content);

    // 安装成功，使用 JavaScript 提示并跳转
    echo '<script>';
    echo 'alert("安装成功！");';
    echo 'window.location.href = "../public/login.php";';
    echo '</script>';


} else {
    // 检查是否已经安装
    if (file_exists('../includes/config.php')) {
        include('../includes/config.php');
        if (defined('INSTALLED') && INSTALLED == 1) {
            die("系统已经安装，请勿重复安装。");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装程序</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1 class="mt-5">安装程序</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <!-- 数据库连接信息 -->
        <h2>数据库连接信息</h2>
        <div class="mb-3">
            <label for="db_host" class="form-label">数据库主机</label>
            <input type="text" class="form-control" id="db_host" name="db_host" required>
        </div>
        <div class="mb-3">
            <label for="db_user" class="form-label">数据库用户名</label>
            <input type="text" class="form-control" id="db_user" name="db_user" required>
        </div>
        <div class="mb-3">
            <label for="db_pass" class="form-label">数据库密码</label>
            <input type="password" class="form-control" id="db_pass" name="db_pass">
        </div>
        <div class="mb-3">
            <label for="db_name" class="form-label">数据库名称</label>
            <input type="text" class="form-control" id="db_name" name="db_name" required>
        </div>

        <!-- 网站配置信息 -->
        <h2>网站配置信息</h2>
        <div class="mb-3">
            <label for="site_name" class="form-label">网站名称</label>
            <input type="text" class="form-control" id="site_name" name="site_name" required>
        </div>
        <div class="mb-3">
            <label for="site_keywords" class="form-label">网站关键词</label>
            <input type="text" class="form-control" id="site_keywords" name="site_keywords" required>
        </div>
        <div class="mb-3">
            <label for="site_description" class="form-label">网站描述</label>
            <textarea class="form-control" id="site_description" name="site_description" required></textarea>
        </div>
        <div class="mb-3">
            <label for="icp_number" class="form-label">备案号</label>
            <input type="text" class="form-control" id="icp_number" name="icp_number">
        </div>
        <div class="mb-3">
            <label for="jobe_server" class="form-label">Jobe 服务器地址</label>
            <input type="text" class="form-control" id="jobe_server" name="jobe_server" required>
        </div>

        <!-- 管理员账号信息 -->
        <h2>管理员账号信息</h2>
        <div class="mb-3">
            <label for="admin_username" class="form-label">管理员用户名</label>
            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
        </div>
        <div class="mb-3">
            <label for="admin_email" class="form-label">管理员邮箱</label>
            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
        </div>
        <div class="mb-3">
            <label for="admin_password" class="form-label">管理员密码</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
        </div>
        <div class="mb-3">
            <label for="admin_password_confirm" class="form-label">确认管理员密码</label>
            <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
        </div>

        <button type="submit" class="btn btn-primary">安装</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>