<?php
// 开启会话
session_start();

// 检查用户是否登录且为管理员
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
    exit;
}

// 包含基础配置和数据库连接
require_once 'config.php';

// 从 site_config 表中读取网站标题、关键词和描述
$config_items = [];
$stmt = $conn->prepare("SELECT name, value FROM site_config WHERE name IN ('site_name', 'site_keywords', 'site_description')");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $config_items[$row['name']] = $row['value'];
}
$stmt->close();

// 提取网站标题、关键词和描述
$site_name = $config_items['site_name'];
$site_keywords = $config_items['site_keywords'];
$site_description = $config_items['site_description'];

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
    <style>
        /* 侧边栏样式 */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 200px;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar li {
            padding: 10px;
        }
        .sidebar a {
            color: #333;
            text-decoration: none;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
        }
    </style>
</head>
<body>
<!-- 侧边栏导航 -->
<div class="sidebar">
    <ul>
        <li><a href="panel.php">数据统计面板</a></li>
        <li><a href="config_management.php">网站基础配置管理</a></li>
        <li><a href="user_management.php">用户管理</a></li>
        <li><a href="category_management.php">题目类别管理</a></li>
        <li><a href="question_management.php">题目管理</a></li>
        <li><a href="../public/index.php">返回前台</a></li>
    </ul>
</div>

<!-- 主内容区域 -->
<div class="main-content">
    <?php echo $content;?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
