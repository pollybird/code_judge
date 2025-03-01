<?php
// 设置页面标题
$page_title = '题目详情';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// 开启输出缓冲
ob_start();

// 引入数据库连接文件
require_once '../includes/config.php';

// 获取题目 ID
$questionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($questionId === 0) {
    // 若未提供有效的题目 ID，跳转到题目列表页（可根据实际情况修改跳转地址）
    header("Location: question_list.php");
    exit;
}

// 查询题目信息
$sql = "SELECT id, title, description FROM questions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $questionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 若未找到对应的题目，显示错误信息
    echo '<div class="alert alert-danger">未找到该题目。</div>';
    exit;
}

$question = $result->fetch_assoc();
$stmt->close();

// 查询题目对应的测试用例
$sqlTestCases = "SELECT id, input, output FROM test_cases WHERE question_id = ?";
$stmtTestCases = $conn->prepare($sqlTestCases);
$stmtTestCases->bind_param("i", $questionId);
$stmtTestCases->execute();
$testCasesResult = $stmtTestCases->get_result();
$testCases = [];
while ($testCase = $testCasesResult->fetch_assoc()) {
    $testCases[] = $testCase;
}
$stmtTestCases->close();
?>
    <div>
        <h1><?php echo htmlspecialchars($question['title']); ?></h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">题目描述</h5>
                <!-- 直接输出问题描述，让 HTML 标签正常渲染 -->
                <p class="card-text"><?php echo $question['description']; ?></p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">输入输出样例</h5>
                <?php foreach ($testCases as $testCase): ?>
                    <div class="mb-3">
                        <h6>输入</h6>
                        <pre><?php echo htmlspecialchars($testCase['input']); ?></pre>
                        <h6>输出</h6>
                        <pre><?php echo htmlspecialchars($testCase['output']); ?></pre>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <form id="answerForm" action="../processes/submit_answer.php" method="post">
            <input type="hidden" name="question_id" value="<?php echo $questionId; ?>">
            <div class="mb-3">
                <label for="language" class="form-label">选择语言</label>
                <select id="language" name="language" class="form-select">
                    <option value="c_cpp">C/C++</option>
                    <option value="java">Java</option>
                    <option value="python">Python</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="answer" class="form-label">你的答案</label>
                <div id="editor" style="height: 300px;"></div>
                <textarea id="answer" name="answer" style="display: none;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">提交答案</button>
        </form>
        <div id="resultDisplay" class="mt-3"></div>
        <script>
            // 初始化 Ace 编辑器
            const editor = ace.edit("editor");
            editor.setTheme("ace/theme/monokai");
            editor.getSession().setMode("ace/mode/c_cpp"); // 默认语言为 C/C++
            // 调大字体大小
            editor.setFontSize(16);

            const languageSelect = document.getElementById('language');
            languageSelect.addEventListener('change', function () {
                const selectedLanguage = this.value;
                const modeMap = {
                    'c_cpp': 'ace/mode/c_cpp',
                    'java': 'ace/mode/java',
                    'python': 'ace/mode/python'
                };
                editor.getSession().setMode(modeMap[selectedLanguage]);
            });

            const form = document.getElementById('answerForm');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                // 在表单提交前，将 Ace 编辑器中的内容同步到隐藏的 textarea 中
                document.getElementById('answer').value = editor.getValue();

                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../processes/submit_answer.php', true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const resultDisplay = document.getElementById('resultDisplay');
                        resultDisplay.innerHTML = xhr.responseText;
                    }
                };
                xhr.send(formData);
            });
        </script>
    </div>

<?php
// 查询历史作答记录
$userId = $_SESSION['user_id'];
$sqlHistory = "SELECT id, code, submitted_at, status FROM submissions WHERE user_id = ? AND question_id = ? ORDER BY submitted_at DESC";
$stmtHistory = $conn->prepare($sqlHistory);

// 检查 SQL 语句是否准备成功
if (!$stmtHistory) {
    die("SQL 语句准备失败: ". $conn->error);
}

$stmtHistory->bind_param("ii", $userId, $questionId);
$stmtHistory->execute();
$historyResult = $stmtHistory->get_result();
$historyRecords = [];
while ($record = $historyResult->fetch_assoc()) {
    $historyRecords[] = $record;
}
$stmtHistory->close();

if (!empty($historyRecords)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">历史作答记录</h5>';
    foreach ($historyRecords as $record) {
        echo '<div class="mb-3">';
        echo '<h6>提交时间：' . $record['submitted_at'] . '</h6>';
        echo '<span>状态：'.$record['status'].'</span>';
        echo '<pre>' . htmlspecialchars($record['code']) . '</pre>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
}
?>

<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含 base.php 母版页
include('../includes/base.php');

// 关闭数据库连接
$conn->close();
?>
