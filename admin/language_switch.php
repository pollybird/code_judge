<?php
// 设置页面标题
$page_title = '编程语言开关及优先级调整';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

$languageConfigFile = __DIR__ . '/../includes/language.json';
$allLanguages = [
    'c_cpp' => 'C/C++', 
    'java' => 'Java', 
    'python' => 'Python', 
    'nodejs' => 'Node.js', 
    'pascal' => 'Pascal'
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabledLanguages = $_POST['languages'] ?? [];
    $priorityLanguages = $_POST['priority'] ?? [];
    // 按照优先级顺序过滤出启用的语言
    $sortedEnabledLanguages = [];
    foreach ($priorityLanguages as $lang) {
        if (in_array($lang, $enabledLanguages)) {
            $sortedEnabledLanguages[] = $lang;
        }
    }
    // 保存排序后的启用语言到配置文件
    $result = file_put_contents($languageConfigFile, json_encode($sortedEnabledLanguages, JSON_PRETTY_PRINT));
    if ($result === false) {
        // 处理保存失败的情况
        error_log("Failed to write to $languageConfigFile");
    }
    header('Location: language_switch.php');
    exit;
}

// 读取当前启用的语言
$currentLanguages = file_exists($languageConfigFile) 
    ? json_decode(file_get_contents($languageConfigFile), true) 
    : [];

// 合并所有语言并按照当前优先级排序
$orderedLanguages = [];
// 先添加有优先级的语言
foreach ($currentLanguages as $lang) {
    if (isset($allLanguages[$lang])) {
        $orderedLanguages[$lang] = $allLanguages[$lang];
    }
}
// 再添加未在优先级列表中的语言
foreach ($allLanguages as $code => $name) {
    if (!in_array($code, $currentLanguages)) {
        $orderedLanguages[$code] = $name;
    }
}

?>
<div class="container py-4">
    <h2 class="mb-4">编程语言开关及优先级调整</h2>
    <form method="post">
        <ul id="language-list" class="list-unstyled">
            <?php foreach ($orderedLanguages as $code => $name): ?>
            <li class="form-check mb-2" draggable="true" data-lang-code="<?= htmlspecialchars($code) ?>">
                <input class="form-check-input" type="checkbox" name="languages[]" 
                       value="<?= htmlspecialchars($code) ?>" id="lang-<?= htmlspecialchars($code) ?>"
                       <?= in_array($code, $currentLanguages) ? 'checked' : '' ?>>
                <label class="form-check-label" for="lang-<?= htmlspecialchars($code) ?>">
                    <?= htmlspecialchars($name) ?>
                </label>
                <input type="hidden" name="priority[]" value="<?= htmlspecialchars($code) ?>">
            </li>
            <?php endforeach; ?>
        </ul>
        <!-- 添加提示语句 -->
        <div class="text-muted mt-2">拖拽可调整顺序</div>
        <button type="submit" class="btn btn-primary mt-3">保存设置</button>
    </form>
</div>
<script>
    const languageList = document.getElementById('language-list');
    let draggedItem = null;

    languageList.addEventListener('dragstart', function(e) {
        if (e.target.closest('li')) {
            draggedItem = e.target.closest('li');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', ''); // 某些浏览器需要设置数据
            draggedItem.style.opacity = '0.5';
        }
    });

    languageList.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });

    languageList.addEventListener('drop', function(e) {
        e.preventDefault();
        const afterElement = getDragAfterElement(languageList, e.clientY);
        const currentItem = draggedItem;
        if (afterElement == null) {
            languageList.appendChild(currentItem);
        } else {
            languageList.insertBefore(currentItem, afterElement);
        }
        // 更新隐藏输入框的顺序
        const items = languageList.querySelectorAll('li');
        items.forEach((item, index) => {
            const input = item.querySelector('input[name="priority[]"]');
            input.value = item.dataset.langCode;
        });
    });

    languageList.addEventListener('dragend', function() {
        draggedItem.style.opacity = '1';
    });

    function getDragAfterElement(container, y) {
        return [...container.children]
            .reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
</script>
<?php
// 获取缓冲区内容
$content = ob_get_clean();

// 包含admin_base.php母版页
require_once __DIR__ . '/../includes/admin_base.php';