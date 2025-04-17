<?php
// 设置页面标题
$page_title = '答题记录';
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 获取题目 ID
$question_id = isset($_GET['question_id']) && is_numeric($_GET['question_id']) ? $_GET['question_id'] : 0;

if ($question_id > 0) {
    // 修改SQL查询，关联users表获取用户名
    $submission_query = "SELECT s.*, u.username 
                         FROM submissions s 
                         JOIN users u ON s.user_id = u.id 
                         WHERE s.question_id = $question_id
                         ORDER BY s.submitted_at DESC";
    $submission_result = $conn->query($submission_query);

    if ($submission_result->num_rows > 0) {
        echo '<h2>题目 ID: ' . $question_id . ' 的答题记录</h2>';
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>ID</th><th>用户名</th><th>语言</th><th>状态</th><th>提交时间</th><th>操作</th></tr></thead>';
        echo '<tbody>';
        while ($submission_row = $submission_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $submission_row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($submission_row['username']) . '</td>';
            echo '<td>' . $submission_row['language'] . '</td>';
            // 添加状态列，根据状态设置不同颜色
            $statusColor = ($submission_row['status'] === 'passed') ? 'text-success' : 'text-danger';
            echo '<td class="' . $statusColor . '">' . $submission_row['status'] . '</td>';
            echo '<td>' . $submission_row['submitted_at'] . '</td>';
            echo '<td><button class="btn btn-sm btn-primary view-detail" 
                      data-id="'.$submission_row['id'].'" 
                      data-code="'.htmlspecialchars($submission_row['code']).'"
                      data-language="'.$submission_row['language'].'">查看详情</button></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        
        // 添加模态框HTML
        echo '
        <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">提交详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div id="codeViewer" style="height: 400px;"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
              </div>
            </div>
          </div>
        </div>';
        
        // 添加JS脚本
        echo '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.0/ace.min.js"></script>
        <script>
        $(document).ready(function() {
            const editor = ace.edit("codeViewer");
            editor.setTheme("ace/theme/monokai");
            editor.setReadOnly(true);
            
            $(".view-detail").click(function() {
                const code = $(this).data("code");
                const language = $(this).data("language");
                
                // 设置语言模式
                const modeMap = {
                    "c_cpp": "ace/mode/c_cpp",
                    "java": "ace/mode/java",
                    "python": "ace/mode/python"
                };
                editor.session.setMode(modeMap[language] || "ace/mode/text");
                
                editor.setValue(code);
                $("#detailModal").modal("show");
            });
        });
        </script>';
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