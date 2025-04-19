<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 包含配置文件
require_once __DIR__. '/config.php';

// 检查数据库连接是否成功
if (!isset($conn) || $conn->connect_error) {
    die("数据库连接失败: ". ($conn? $conn->connect_error : '未定义连接变量'));
}

// 从 site_config 表中读取网站标题、关键词和描述
$config_items = [];
$stmt = $conn->prepare("SELECT name, value FROM site_config WHERE name IN ('site_name', 'site_keywords', 'site_description')");
if (!$stmt) {
    die("准备 SQL 语句失败: ". $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $config_items[$row['name']] = $row['value'];
}
$stmt->close();

// 提取网站标题、关键词和描述
$site_name = $config_items['site_name']?? '编程题库系统';
$site_keywords = $config_items['site_keywords']?? '';
$site_description = $config_items['site_description']?? '';

// 如果有具体页面标题，拼接在网站标题后面
$page_title = isset($page_title)? $page_title. ' - '. $site_name : $site_name;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title;?></title>
    <meta name="keywords" content="<?php echo $site_keywords;?>">
    <meta name="description" content="<?php echo $site_description;?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- 引入 Ace 编辑器的核心文件 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/ace.min.js"></script>
    <!-- 引入所需的 Ace 语言模式 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/mode-c_cpp.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/mode-java.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/mode-python.min.js"></script>
    <!-- 引入 Ace 主题 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/theme-monokai.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<!-- 导航栏 -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <!-- 品牌或首页链接 -->
        <a class="navbar-brand" href="index.php">首页</a>
        <!-- 导航栏切换按钮 -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- 导航栏链接 -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php
                // 引入数据库连接文件
                require_once '../includes/config.php';
                // 查询所有分类
                $sql = "SELECT id, name FROM categories";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($category = $result->fetch_assoc()) {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link" href="question_list.php?category_id='.$category['id'].'">'.$category['name'].'</a>';
                    echo '</li>';
                }
                $stmt->close();
                ?>
            </ul>
            <ul class="navbar-nav">
                <?php
                if (isset($_SESSION['user_id'])) {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link" href="change_password.php">修改密码</a>';
                    echo '</li>';
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link" href="logout.php">注销登录</a>';
                    echo '</li>';
                    // 假设管理员用户的角色 ID 为 1，可根据实际情况修改
                    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link" href="../admin/panel.php">登录后台</a>';
                        echo '</li>';
                    }
                }else{
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link" href="login.php">登录</a>';
                    echo '</li>';
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link" href="register.php">注册</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<!-- 页面内容 -->
<div class="container flex-grow-1">
    <?php
    if (!isset($content)) {
        $content = '';
    }
    echo $content;
    ?>
</div>

<footer class="bg-primary py-3 mt-auto">
    <?php
    // 查询备案号
    $sql = "SELECT value FROM site_config WHERE name = 'icp_number'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $icpNumber = '';
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $icpNumber = $row['value'];
    }
    $stmt->close();
    ?>
    <div class="container">
        <p class="text-center mb-0 text-white">&copy; 2025 <?php echo htmlspecialchars($site_name); ?>. All rights reserved. | 备案号：<?php echo htmlspecialchars($icpNumber); ?></p>
    </div>
</footer>

<!-- 引入 Bootstrap JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>