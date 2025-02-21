<?php
// 设置页面标题
$page_title = '题目类别管理';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 处理删除类别请求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id =?");
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success" role="alert">类别删除成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">类别删除失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 处理新增类别请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success" role="alert">类别添加成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">类别添加失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 处理修改类别请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = $_POST['category_name'];
    $stmt = $conn->prepare("UPDATE categories SET name =? WHERE id =?");
    $stmt->bind_param("si", $category_name, $category_id);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success" role="alert">类别修改成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">类别修改失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 查询所有类别信息
$category_query = "SELECT id, name FROM categories";
$category_result = $conn->query($category_query);
?>

    <div class="row">
        <div class="col-md-12">
            <h2>题目类别管理</h2>
            <!-- 添加类别按钮 -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                添加类别
            </button>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>类别名称</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ($category_result->num_rows > 0) {
                    while ($category_row = $category_result->fetch_assoc()) {
                        $category_id = $category_row['id'];
                        $category_name = $category_row['name'];
                        echo '<tr>';
                        echo '<td>' . $category_id . '</td>';
                        echo '<td>' . htmlspecialchars($category_name) . '</td>';
                        echo '<td>';
                        echo '<button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCategoryModal-' . $category_id . '">修改</button> ';
                        echo '<a href="?delete=' . $category_id . '" class="btn btn-danger btn-sm" onclick="return confirm(\'确定要删除该类别吗？\')">删除</a>';
                        echo '</td>';
                        echo '</tr>';

                        // 编辑类别模态框
                        echo '<div class="modal fade" id="editCategoryModal-' . $category_id . '" tabindex="-1" aria-labelledby="editCategoryModalLabel-' . $category_id . '" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="editCategoryModalLabel-' . $category_id . '">修改类别</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        echo '<form method="post">';
                        echo '<input type="hidden" name="category_id" value="' . $category_id . '">';
                        echo '<div class="mb-3">';
                        echo '<label for="edit_category_name-' . $category_id . '" class="form-label">类别名称</label>';
                        echo '<input type="text" class="form-control" id="edit_category_name-' . $category_id . '" name="category_name" value="' . htmlspecialchars($category_name) . '" required>';
                        echo '</div>';
                        echo '<button type="submit" class="btn btn-primary" name="edit_category">保存修改</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<tr><td colspan="3">暂无类别记录。</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 添加类别模态框 -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">添加类别</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="add_category_name" class="form-label">类别名称</label>
                            <input type="text" class="form-control" id="add_category_name" name="category_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_category">添加</button>
                    </form>
                </div>
            </div>
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