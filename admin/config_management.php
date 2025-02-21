<?php
// 设置页面标题
$page_title = '网站基础配置管理';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 获取当前配置信息
$config_items = [];
$stmt = $conn->prepare("SELECT name, value FROM site_config");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $config_items[$row['name']] = $row['value'];
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 处理表单提交
    foreach ($_POST as $name => $value) {
        $stmt = $conn->prepare("UPDATE site_config SET value =? WHERE name =?");
        $stmt->bind_param("ss", $value, $name);
        $stmt->execute();
        $stmt->close();
    }
    echo '<div class="alert alert-success" role="alert">配置更新成功！</div>';
}
?>
    <div class="row">
        <div class="col-md-8">
            <h2>网站基础配置管理</h2>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="site_name" class="form-label">网站名称</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($config_items['site_name']?? '');?>">
                </div>
                <div class="mb-3">
                    <label for="site_keywords" class="form-label">网站关键词</label>
                    <input type="text" class="form-control" id="site_keywords" name="site_keywords" value="<?php echo htmlspecialchars($config_items['site_keywords']?? '');?>">
                </div>
                <div class="mb-3">
                    <label for="site_description" class="form-label">网站描述</label>
                    <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($config_items['site_description']?? '');?></textarea>
                </div>
                <div class="mb-3">
                    <label for="icp_number" class="form-label">备案号</label>
                    <input type="text" class="form-control" id="icp_number" name="icp_number" value="<?php echo htmlspecialchars($config_items['icp_number']?? '');?>">
                </div>
                <div class="mb-3">
                    <label for="jobe_server" class="form-label">Jobe 服务器地址</label>
                    <input type="text" class="form-control" id="jobe_server" name="jobe_server" value="<?php echo htmlspecialchars($config_items['jobe_server']?? '');?>">
                </div>
                <button type="submit" class="btn btn-primary">保存配置</button>
            </form>
        </div>
    </div>
<?php
// 获取并清空输出缓冲区的内容
$content = ob_get_clean();
// 包含管理后台母版页
include('../includes/admin_base.php');
?>