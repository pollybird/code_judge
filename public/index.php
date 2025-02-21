<?php
// 设置页面标题
$page_title = '首页';

session_start();
// 开启输出缓冲
ob_start();

// 引入数据库连接文件
require_once '../includes/config.php';

// 查询所有分类
$sqlCategories = "SELECT id, name FROM categories";
$stmtCategories = $conn->prepare($sqlCategories);
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
                                $stmtQuestions->bind_param("i", $category['id']);
                                $stmtQuestions->execute();
                                $resultQuestions = $stmtQuestions->get_result();
                                while ($question = $resultQuestions->fetch_assoc()) {
                                    echo '<li class="list-group-item">';
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