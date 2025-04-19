<?php
// 设置页面标题
$page_title = '数据统计面板';

// 开启输出缓冲
ob_start();

// 包含数据库连接配置文件
require_once '../includes/config.php';

// 查询各分类题目统计数据
// 修改SQL查询中的正确率计算方式
$categoryStatsQuery = "SELECT 
    c.name AS category_name,
    COUNT(DISTINCT s.question_id) AS question_count,
    COUNT(s.id) AS submission_count,
    SUM(CASE WHEN s.status = 'passed' THEN 1 ELSE 0 END) AS passed_count,
    CASE 
        WHEN COUNT(s.id) = 0 THEN 0
        ELSE ROUND(SUM(CASE WHEN s.status = 'passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(s.id), 2)
    END AS pass_rate
FROM categories c
LEFT JOIN questions q ON c.id = q.category_id
LEFT JOIN submissions s ON q.id = s.question_id
GROUP BY c.id
ORDER BY submission_count DESC";

// 同样修改编程语言统计的SQL
$languageStatsQuery = "SELECT 
    language,
    COUNT(*) AS usage_count,
    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) AS passed_count,
    CASE 
        WHEN COUNT(*) = 0 THEN 0
        ELSE ROUND(SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2)
    END AS pass_rate
FROM submissions
GROUP BY language
ORDER BY usage_count DESC";

$categoryStatsResult = $conn->query($categoryStatsQuery);
$categoryStats = [];
while ($row = $categoryStatsResult->fetch_assoc()) {
    $categoryStats[] = $row;
}

// 查询编程语言使用统计数据
$languageStatsQuery = "SELECT 
    language,
    COUNT(*) AS usage_count,
    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) AS passed_count,
    AVG(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) * 100 AS pass_rate
FROM submissions
GROUP BY language
ORDER BY usage_count DESC";

$languageStatsResult = $conn->query($languageStatsQuery);
$languageStats = [];
while ($row = $languageStatsResult->fetch_assoc()) {
    $languageStats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            height: 400px;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <h2 class="my-4"><?php echo $page_title; ?></h2>
    
    <!-- 题目分类统计 -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">题目分类统计</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>分类名称</th>
                            <th>题目数量</th>
                            <th>作答次数</th>
                            <th>正确次数</th>
                            <th>正确率</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryStats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['category_name']); ?></td>
                            <td><?php echo $stat['question_count']; ?></td>
                            <td><?php echo $stat['submission_count']; ?></td>
                            <td><?php echo $stat['passed_count']; ?></td>
                            <td><?php echo number_format($stat['pass_rate'], 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 编程语言统计 -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">编程语言统计</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="languageUsageChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="languagePassRateChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>编程语言</th>
                            <th>使用次数</th>
                            <th>正确次数</th>
                            <th>正确率</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languageStats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['language']); ?></td>
                            <td><?php echo $stat['usage_count']; ?></td>
                            <td><?php echo $stat['passed_count']; ?></td>
                            <td><?php echo number_format($stat['pass_rate'], 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 题目分类统计图表
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($categoryStats, 'category_name')); ?>,
            datasets: [
                {
                    label: '作答次数',
                    data: <?php echo json_encode(array_column($categoryStats, 'submission_count')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: '正确次数',
                    data: <?php echo json_encode(array_column($categoryStats, 'passed_count')); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 编程语言使用比例图表
    const languageUsageCtx = document.getElementById('languageUsageChart').getContext('2d');
    new Chart(languageUsageCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($languageStats, 'language')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($languageStats, 'usage_count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '编程语言使用比例'
                }
            }
        }
    });

    // 编程语言正确率图表
    const languagePassRateCtx = document.getElementById('languagePassRateChart').getContext('2d');
    new Chart(languagePassRateCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($languageStats, 'language')); ?>,
            datasets: [{
                label: '正确率(%)',
                data: <?php echo json_encode(array_column($languageStats, 'pass_rate')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: '各语言正确率'
                }
            }
        }
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