<?php
// 设置页面标题
$page_title = '答题记录';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 获取题目 ID
$question_id = isset($_GET['question_id']) && is_numeric($_GET['question_id']) ? $_GET['question_id'] : 0;

if ($question_id > 0) {
    // 查询该题目的答题记录，假设答题记录存储在 submissions 表中
    $submission_query = "SELECT * FROM submissions WHERE question_id = $question_id";
    $submission_result = $conn->query($submission_query);

    if ($submission_result->num_rows > 0) {
        echo '<h2>题目 ID: ' . $question_id . ' 的答题记录</h2>';
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>ID</th><th>用户 ID</th><th>提交内容</th><th>提交时间</th></tr></thead>';
        echo '<tbody>';
        while ($submission_row = $submission_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $submission_row['id'] . '</td>';
            echo '<td>' . $submission_row['user_id'] . '</td>';
            echo '<td>' . $submission_row['language'] . '</td>';
            echo '<td>' . htmlspecialchars($submission_row['code']) . '</td>';
            echo '<td>' . $submission_row['submitted_at'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>该题目暂无答题记录。</p>';
    }
} else {
    echo '<p>未指定有效的题目 ID。</p>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title;?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
</head>
<body>
</body>
</html>