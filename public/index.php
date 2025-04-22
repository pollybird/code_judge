<?php
// 启动会话
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 设置页面标题
$page_title = '首页';

// 开启输出缓冲
ob_start();

// 引入数据库连接文件
require_once '../includes/config.php';

// 查询所有分类
$sqlCategories = "SELECT id, name FROM categories order by id asc";
$stmtCategories = $conn->prepare($sqlCategories);
if (!$stmtCategories) {
    die('Prepare failed for categories query: ' . $conn->error);
}
$stmtCategories->execute();
$resultCategories = $stmtCategories->get_result();
$categories = $resultCategories->fetch_all(MYSQLI_ASSOC);
$stmtCategories->close();
?>
    <div class="container">
        <h1>题目分类列表</h1>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <ul class="list-group list-group-flush">
                                <?php
                                // 查询该分类下的前 5 道题
                                $sqlQuestions = "SELECT id, title FROM questions WHERE category_id = ? LIMIT 5";
                                $stmtQuestions = $conn->prepare($sqlQuestions);
                                if (!$stmtQuestions) {
                                    die('Prepare failed for questions query: ' . $conn->error);
                                }
                                $stmtQuestions->bind_param("i", $category['id']);
                                $stmtQuestions->execute();
                                $resultQuestions = $stmtQuestions->get_result();
                                while ($question = $resultQuestions->fetch_assoc()) {
                                    // 查询用户是否有通过该题的记录
                                    $sqlRecord = "SELECT 1 FROM submissions WHERE user_id = ? AND question_id = ? AND `status` = 'passed' LIMIT 1";
                                    $stmtRecord = $conn->prepare($sqlRecord);
                                    if (!$stmtRecord) {
                                        die('Prepare failed for record query: ' . $conn->error);
                                    }
                                    $stmtRecord->bind_param("ii", $_SESSION['user_id'], $question['id']);
                                    $stmtRecord->execute();
                                    $resultRecord = $stmtRecord->get_result();
                                    $hasPassed = $resultRecord->num_rows > 0;
                                    $stmtRecord->close();

                                    $statusClass = 'text-secondary'; // 未作答默认灰色
                                    if ($hasPassed) {
                                        $statusClass = 'text-success'; // 作答正确绿色
                                    } else {
                                        // 查询最新一次记录判断是否失败
                                        $sqlLatestRecord = "SELECT `status` FROM submissions WHERE user_id = ? AND question_id = ? ORDER BY submitted_at DESC LIMIT 1";
                                        $stmtLatestRecord = $conn->prepare($sqlLatestRecord);
                                        if (!$stmtLatestRecord) {
                                            die('Prepare failed for latest record query: ' . $conn->error);
                                        }
                                        $stmtLatestRecord->bind_param("ii", $_SESSION['user_id'], $question['id']);
                                        $stmtLatestRecord->execute();
                                        $resultLatestRecord = $stmtLatestRecord->get_result();
                                        $latestRecord = $resultLatestRecord->fetch_assoc();
                                        $stmtLatestRecord->close();

                                        if ($latestRecord && $latestRecord['status'] === 'failed') {
                                            $statusClass = 'text-danger'; // 作答错误红色
                                        }
                                    }

                                    echo '<li class="list-group-item">';
                                    echo '<span class="' . $statusClass . '">●</span> '; // 状态提示符号
                                    echo '<a href="question.php?id=' . $question['id'] . '">';
                                    echo htmlspecialchars($question['title']);
                                    echo '</a>';
                                    echo '</li>';
                                }
                                $stmtQuestions->close();
                                ?>
                            </ul>
                        </div>
                        <div class="card-footer text-end">
                            <a href="question_list.php?category_id=<?php echo $category['id']; ?>" class="btn btn-link">more</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含 base.php 母版页
include('../includes/base.php');

// 关闭数据库连接
$conn->close();
?>