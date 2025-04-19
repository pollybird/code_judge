<?php
// 设置页面标题
$page_title = '题目管理';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 每页显示的题目数量
$limit = 10;

// 获取当前页码
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 获取筛选的类别 ID
$category_id = isset($_GET['category']) && is_numeric($_GET['category']) ? $_GET['category'] : 0;

// 处理删除题目请求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $question_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE id =?");
    $stmt->bind_param("i", $question_id);
    if ($stmt->execute()) {
        // 同时删除该题目的测试用例
        $delete_test_case_stmt = $conn->prepare("DELETE FROM test_cases WHERE question_id =?");
        $delete_test_case_stmt->bind_param("i", $question_id);
        $delete_test_case_stmt->execute();
        $delete_test_case_stmt->close();
        echo '<div class="alert alert-success" role="alert">题目删除成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">题目删除失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 处理新增题目请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $category_id = $_POST['category_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO questions (category_id, title, description) VALUES (?,?,?)");
    $stmt->bind_param("iss", $category_id, $title, $description);
    if ($stmt->execute()) {
        $question_id = $conn->insert_id;

        // 插入测试用例
        $input_cases = $_POST['input_case'];
        $output_cases = $_POST['output_case'];
        for ($i = 0; $i < count($input_cases); $i++) {
            $input = $input_cases[$i];
            $output = $output_cases[$i];
            $test_case_stmt = $conn->prepare("INSERT INTO test_cases (question_id, input, output) VALUES (?,?,?)");
            $test_case_stmt->bind_param("iss", $question_id, $input, $output);
            $test_case_stmt->execute();
            $test_case_stmt->close();
        }

        echo '<div class="alert alert-success" role="alert">题目添加成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">题目添加失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 处理修改题目请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_question'])) {
    $question_id = $_POST['question_id'];
    $category_id = $_POST['category_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE questions SET category_id =?, title =?, description =? WHERE id =?");
    $stmt->bind_param("issi", $category_id, $title, $description, $question_id);
    if ($stmt->execute()) {
        // 先删除原有的测试用例
        $delete_test_case_stmt = $conn->prepare("DELETE FROM test_cases WHERE question_id =?");
        $delete_test_case_stmt->bind_param("i", $question_id);
        $delete_test_case_stmt->execute();
        $delete_test_case_stmt->close();

        // 插入新的测试用例
        $input_cases = $_POST['input_case'];
        $output_cases = $_POST['output_case'];
        for ($i = 0; $i < count($input_cases); $i++) {
            $input = $input_cases[$i];
            $output = $output_cases[$i];
            $test_case_stmt = $conn->prepare("INSERT INTO test_cases (question_id, input, output) VALUES (?,?,?)");
            $test_case_stmt->bind_param("iss", $question_id, $input, $output);
            $test_case_stmt->execute();
            $test_case_stmt->close();
        }

        echo '<div class="alert alert-success" role="alert">题目修改成功！</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">题目修改失败：' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 查询所有类别信息
$category_query = "SELECT id, name FROM categories";
$category_result = $conn->query($category_query);

// 构建筛选条件
$where_clause = '';
if ($category_id > 0) {
    $where_clause = " WHERE q.category_id = $category_id";
}

// 查询题目总数
$total_query = "SELECT COUNT(*) as total FROM questions q $where_clause";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_questions = $total_row['total'];
$total_pages = ceil($total_questions / $limit);

// 查询当前页的题目信息
$question_query = "SELECT q.id, q.title, c.name as category_name 
                   FROM questions q 
                   JOIN categories c ON q.category_id = c.id 
                   $where_clause 
                   LIMIT $offset, $limit";
$question_result = $conn->query($question_query);
?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title;?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <script src="https://cdn.tiny.cloud/1/9i540cxuimri8jdbv33qy0cfo5rstgq5ox7occ1vi6lkz16p/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <style>
            /* 模态框最大化样式 */
            .modal-maximized {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                margin: 0;
            }

            .modal-maximized .modal-dialog {
                max-width: 100%;
                height: 100%;
                margin: 0;
            }

            .modal-maximized .modal-content {
                height: 100%;
            }

            .modal-maximized .modal-body {
                /* 精确计算高度，减去模态框头部和底部的高度 */
                height: calc(100% - 56px - 1px);
                overflow-y: auto;
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                /* 调整子元素之间的间距 */
                gap: 10px;
            }
        </style>
    </head>
<body>
<div class="row">
    <div class="col-md-12">
    <h2>题目管理</h2>
    <!-- 类别筛选 -->
    <form class="mb-3" method="get">
        <select name="category" class="form-select" style="width: auto; display: inline-block;">
            <option value="0">所有类别</option>
            <?php
            if ($category_result->num_rows > 0) {
                while ($category_row = $category_result->fetch_assoc()) {
                    $selected = ($category_row['id'] == $category_id) ? 'selected' : '';
                    echo '<option value="' . $category_row['id'] . '" ' . $selected . '>' . $category_row['name'] . '</option>';
                }
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary">筛选</button>
    </form>
    <!-- 添加题目按钮 -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
        添加题目
    </button>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>题目标题</th>
            <th>所属类别</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
<?php
if ($question_result->num_rows > 0) {
    while ($question_row = $question_result->fetch_assoc()) {
        $question_id = $question_row['id'];
        $question_title = $question_row['title'];
        $category_name = $question_row['category_name'];
        echo '<tr>';
        echo '<td>' . $question_id . '</td>';
        echo '<td>' . htmlspecialchars($question_title) . '</td>';
        echo '<td>' . htmlspecialchars($category_name) . '</td>';
        echo '<td>';
        echo '<button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editQuestionModal-' . $question_id . '">修改</button> ';
        echo '<a href="?delete=' . $question_id . '&category=' . $category_id . '&page=' . $page . '" class="btn btn-danger btn-sm mx-2" onclick="return confirm(\'确定要删除该题目吗？\')">删除</a>';
        echo '<a href="submission.php?question_id=' . $question_id . '" class="btn btn-info btn-sm" target="_blank">查看作答</a>';
        echo '</td>';
        echo '</tr>';

        // 编辑题目模态框
        $edit_question_stmt = $conn->prepare("SELECT q.id, q.category_id, q.title, q.description 
                                                                  FROM questions q 
                                                                  WHERE q.id =?");
        $edit_question_stmt->bind_param("i", $question_id);
        $edit_question_stmt->execute();
        $edit_question_result = $edit_question_stmt->get_result();
        $edit_question_row = $edit_question_result->fetch_assoc();

        $test_case_stmt = $conn->prepare("SELECT id, input, output FROM test_cases WHERE question_id =?");
        $test_case_stmt->bind_param("i", $question_id);
        $test_case_stmt->execute();
        $test_case_result = $test_case_stmt->get_result();
        $test_cases = [];
        while ($test_case_row = $test_case_result->fetch_assoc()) {
            $test_cases[] = $test_case_row;
        }

        echo '<div class="modal fade modal-maximized" id="editQuestionModal-' . $question_id . '" tabindex="-1" aria-labelledby="editQuestionModalLabel-' . $question_id . '" aria-hidden="true">';
        echo '<div class="modal-dialog">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title" id="editQuestionModalLabel-' . $question_id . '">修改题目</h5>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '<button type="button" class="btn btn-sm btn-secondary maximize-modal" data-modal-id="editQuestionModal-' . $question_id . '">';
        echo '<i class="fa-solid fa-compress"></i>';
        echo '</button>';
        echo '</div>';
        echo '<div class="modal-body" style="overflow-y: auto;">';
        echo '<form method="post">';
        echo '<input type="hidden" name="question_id" value="' . $question_id . '">';
        echo '<div class="mb-3">';
        echo '<label for="edit_category_id-' . $question_id . '" class="form-label">所属类别</label>';
        echo '<select name="category_id" class="form-select" id="edit_category_id-' . $question_id . '">';
        $category_result->data_seek(0);
        if ($category_result->num_rows > 0) {
            while ($category_row = $category_result->fetch_assoc()) {
                $selected = ($category_row['id'] == $edit_question_row['category_id']) ? 'selected' : '';
                echo '<option value="' . $category_row['id'] . '" ' . $selected . '>' . $category_row['name'] . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label for="edit_title-' . $question_id . '" class="form-label">题目标题</label>';
        echo '<input type="text" class="form-control" id="edit_title-' . $question_id . '" name="title" value="' . htmlspecialchars($edit_question_row['title']) . '" required>';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label for="edit_description-' . $question_id . '" class="form-label">题目描述</label>';
        echo '<textarea class="form-control" id="edit_description-' . $question_id . '" name="description" rows="10" required>' . htmlspecialchars($edit_question_row['description']) . '</textarea>';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">输入输出样例</label>';
        foreach ($test_cases as $index => $test_case) {
            echo '<div class="input-group mb-3">';
            echo '<div class="input-group-prepend">';
            echo '<span class="input-group-text">输入</span>';
            echo '</div>';
            echo '<textarea class="form-control" name="input_case[]" rows="3">' . htmlspecialchars($test_case['input']) . '</textarea>';
            echo '<div class="input-group-prepend">';
            echo '<span class="input-group-text"></span>';
            echo '</div>';
            echo '<textarea class="form-control" name="input_case[]" rows="3">' . htmlspecialchars($test_case['input']) . '</textarea>';
            echo '<div class="input-group-prepend">';
            echo '<span class="input-group-text">输出</span>';
            echo '</div>';
            echo '<textarea class="form-control" name="output_case[]" rows="3">' . htmlspecialchars($test_case['output']) . '</textarea>';
            if ($index > 0) {
                echo '<button type="button" class="btn btn-danger remove-test-case">删除</button>';
            }
            echo '</div>';
        }
        echo '<button type="button" class="btn btn-success add-test-case">添加一组</button>';
        echo '</div>';
        echo '<button type="submit" class="btn btn-primary" name="edit_question">保存修改</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        $edit_question_stmt->close();
        $test_case_stmt->close();
    }
} else {
    echo '<tr><td colspan="4">暂无题目记录。</td></tr>';
}
?>
        </tbody>
    </table>

        <!-- 分页导航 -->
        <nav aria-label="题目列表分页">
            <ul class="pagination">
                <?php
                if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?category=' . $category_id . '&page=' . ($page - 1) . '">上一页</a></li>';
                }
                for ($i = 1; $i <= $total_pages; $i++) {
                    $active_class = ($i == $page) ? 'active' : '';
                    echo '<li class="page-item ' . $active_class . '"><a class="page-link" href="?category=' . $category_id . '&page=' . $i . '">' . $i . '</a></li>';
                }
                if ($page < $total_pages) {
                    echo '<li class="page-item"><a class="page-link" href="?category=' . $category_id . '&page=' . ($page + 1) . '">下一页</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</div>

<!-- 添加题目模态框 -->
<div class="modal fade modal-maximized" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addQuestionModalLabel">添加题目</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <button type="button" class="btn btn-sm btn-secondary maximize-modal" data-modal-id="addQuestionModal">
                    <i class="fa-solid fa-compress"></i>
                </button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="add_category_id" class="form-label">所属类别</label>
                        <select name="category_id" class="form-select" id="add_category_id">
                            <?php
                            $category_result->data_seek(0);
                            if ($category_result->num_rows > 0) {
                                while ($category_row = $category_result->fetch_assoc()) {
                                    echo '<option value="' . $category_row['id'] . '">' . $category_row['name'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_title" class="form-label">题目标题</label>
                        <input type="text" class="form-control" id="add_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">题目描述</label>
                        <textarea class="form-control" id="add_description" name="description" rows="10"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">输入输出样例</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">输入</span>
                            </div>
                            <textarea class="form-control" name="input_case[]" rows="3"></textarea>
                            <div class="input-group-prepend">
                                <span class="input-group-text">输出</span>
                            </div>
                            <textarea class="form-control" name="output_case[]" rows="3"></textarea>
                        </div>
                        <button type="button" class="btn btn-success add-test-case">添加一组</button>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_question">添加</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 初始化 TinyMCE 用于添加题目描述
        tinymce.init({
            selector: '#add_description',
            language: 'zh_CN',
            plugins: 'lists link image media',
            toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media',
            height: 300
        });

        // 初始化 TinyMCE 用于编辑题目描述
        const editDescriptions = document.querySelectorAll('[id^=edit_description-]');
        editDescriptions.forEach(function (desc) {
            tinymce.init({
                selector: '#' + desc.id,
                language: 'zh_CN',
                plugins: 'lists link image media',
                toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media',
                height: 300
            });
        });

        // 动态添加输入输出样例组
        const addTestCaseButtons = document.querySelectorAll('.add-test-case');
        addTestCaseButtons.forEach(button => {
            button.addEventListener('click', function () {
                const inputGroup = this.previousElementSibling.cloneNode(true);
                const textareas = inputGroup.querySelectorAll('textarea');
                textareas.forEach(textarea => {
                    textarea.value = '';
                });
                
                // 移除原有的删除按钮
                const existingRemoveButtons = inputGroup.querySelectorAll('.remove-test-case');
                existingRemoveButtons.forEach(btn => btn.remove());
                
                // 添加新的删除按钮
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.classList.add('btn', 'btn-danger', 'remove-test-case');
                removeButton.textContent = '删除';
                inputGroup.appendChild(removeButton);
                
                this.parentNode.insertBefore(inputGroup, this);
            });
        });

        // 动态删除输入输出样例组
        document.body.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-test-case')) {
                event.target.parentNode.remove();
            }
        });

        // 手动验证添加题目表单
        const addQuestionForm = document.querySelector('#addQuestionModal form');
        if (addQuestionForm) {
            addQuestionForm.addEventListener('submit', function (e) {
                const title = this.querySelector('input[name="title"]').value;
                const description = tinymce.get('add_description').getContent();

                if (title === '' || description === '') {
                    e.preventDefault();
                    alert('请填写题目标题和题目描述');
                } else {
                    // 同步 TinyMCE 内容到 textarea
                    tinymce.triggerSave();
                    this.submit();
                }
            });
        }

        // 手动验证编辑题目表单
        const editQuestionForms = document.querySelectorAll('[id^=editQuestionModal-] form');
        editQuestionForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const questionId = this.querySelector('input[name="question_id"]').value;
                const title = this.querySelector('input[name="title"]').value;
                const description = tinymce.get('edit_description-' + questionId).getContent();

                if (title === '' || description === '') {
                    e.preventDefault();
                    alert('请填写题目标题和题目描述');
                } else {
                    // 同步 TinyMCE 内容到 textarea
                    tinymce.triggerSave();
                    this.submit();
                }
            });
        });

        // 模态框最大化功能
        const maximizeButtons = document.querySelectorAll('.maximize-modal');
        maximizeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const modalId = this.dataset.modalId;
                const modal = document.getElementById(modalId);
                const dialog = modal.querySelector('.modal-dialog');
                const icon = this.querySelector('i');
                if (modal.classList.contains('modal-maximized')) {
                    modal.classList.remove('modal-maximized');
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                } else {
                    modal.classList.add('modal-maximized');
                    icon.classList.remove('fa-expand');
                    icon.classList.add('fa-compress');
                }
            });
        });
    });
</script>
</body>
</html>

<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含管理后台母版页
include('../includes/admin_base.php');

// 关闭数据库连接
$conn->close();
?>