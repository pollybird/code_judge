<?php
// 设置页面标题
$page_title = '题目列表';

// 开启输出缓冲
ob_start();

// 引入数据库连接文件
require_once '../includes/config.php';

// 检查用户是否已登录
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 获取分类 ID
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($categoryId === 0) {
    // 若未提供有效的分类 ID，跳转到默认页面（可根据实际情况修改跳转地址）
    header("Location: index.php");
    exit;
}

// 获取当前页码，默认为第 1 页
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$itemsPerPage = 10; // 每页显示 10 道题
$offset = ($page - 1) * $itemsPerPage;

// 查询该分类下的题目总数
$sqlCount = "SELECT COUNT(*) as total FROM questions WHERE category_id = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $categoryId);
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$rowCount = $resultCount->fetch_assoc();
$totalItems = $rowCount['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$stmtCount->close();

// 查询当前页的题目
$sql = "SELECT id, title FROM questions WHERE category_id = ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $categoryId, $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$questions = [];
while ($question = $result->fetch_assoc()) {
    // 查询当前用户对该题的最后一次答题记录
    $sqlSubmission = "SELECT status FROM submissions 
                      WHERE user_id = ? AND question_id = ? 
                      ORDER BY submitted_at DESC LIMIT 1";
    $stmtSubmission = $conn->prepare($sqlSubmission);
    $stmtSubmission->bind_param("ii", $_SESSION['user_id'], $question['id']);
    $stmtSubmission->execute();
    $resultSubmission = $stmtSubmission->get_result();
    if ($resultSubmission->num_rows === 0) {
        $question['status'] = 'not_answered';
    } else {
        $rowSubmission = $resultSubmission->fetch_assoc();
        $question['status'] = $rowSubmission['status'];
    }
    $stmtSubmission->close();
    $questions[] = $question;
}
$stmt->close();
?>
    <div>
        <h1><?php
            // 查询分类名称
            $sqlCategory = "SELECT name FROM categories WHERE id = ?";
            $stmtCategory = $conn->prepare($sqlCategory);
            $stmtCategory->bind_param("i", $categoryId);
            $stmtCategory->execute();
            $resultCategory = $stmtCategory->get_result();
            if ($resultCategory->num_rows > 0) {
                $category = $resultCategory->fetch_assoc();
                echo htmlspecialchars($category['name']);
            }
            $stmtCategory->close();
            ?> 分类下的题目列表</h1>
        <ul class="list-group">
            <?php foreach ($questions as $question): ?>
                <li class="list-group-item">
                    <?php
                    if ($question['status'] === 'not_answered') {
                        echo '<span class="circle" style="background-color: gray;" title="未作答"></span>';
                    } elseif ($question['status'] === 'passed') {
                        echo '<span class="circle" style="background-color: green;" title="已通过"></span>';
                    } else {
                        echo '<span class="circle" style="background-color: red;" title="未通过"></span>';
                    }
                    ?>
                    <a href="question.php?id=<?php echo $question['id']; ?>">
                        <?php echo htmlspecialchars($question['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- 分页导航 -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="question_list.php?category_id=<?php echo $categoryId; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php if ($i === $page) echo 'active'; ?>">
                        <a class="page-link" href="question_list.php?category_id=<?php echo $categoryId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="question_list.php?category_id=<?php echo $categoryId; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含 base.php 母版页
include('../includes/base.php');

// 关闭数据库连接
$conn->close();
?>