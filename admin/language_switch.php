<?php
// 设置页面标题
$page_title = '编程语言开关';

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
    file_put_contents($languageConfigFile, json_encode($enabledLanguages));
    header('Location: language_switch.php');
    exit;
}

// 读取当前启用的语言
$currentLanguages = file_exists($languageConfigFile) 
    ? json_decode(file_get_contents($languageConfigFile), true) 
    : [];

?>
<div class="container py-4">
    <h2 class="mb-4">编程语言开关</h2>
    <form method="post">
        <?php foreach ($allLanguages as $code => $name): ?>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="languages[]" 
                   value="<?= htmlspecialchars($code) ?>" id="lang-<?= htmlspecialchars($code) ?>"
                   <?= in_array($code, $currentLanguages) ? 'checked' : '' ?>>
            <label class="form-check-label" for="lang-<?= htmlspecialchars($code) ?>">
                <?= htmlspecialchars($name) ?>
            </label>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary mt-3">保存设置</button>
    </form>
</div>
<?php
// 获取缓冲区内容
$content = ob_get_clean();

// 包含admin_base.php母版页
require_once __DIR__ . '/../includes/admin_base.php';