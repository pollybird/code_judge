<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $language = isset($_POST['language']) ? $_POST['language'] : '';
    $answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if ($questionId === 0 || empty($language) || empty($answer) || $userId === 0) {
        echo '<div class="alert alert-danger">提交的数据不完整，请重试。</div>';
        exit;
    }

    // 获取 Jobe 服务器地址
    $jobeServer = get_site_config($conn, 'jobe_server');
    if (!$jobeServer) {
        echo '<div class="alert alert-danger">Jobe 服务器未配置。</div>';
        exit;
    }

    // 获取测试用例
    $sql = "SELECT input, output FROM test_cases WHERE question_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $testCases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($testCases)) {
        echo '<div class="alert alert-warning">此题目没有测试用例。</div>';
        exit;
    }

    $languageMap = [
        'c_cpp' => 'c',       // C
        'cpp' => 'cpp',       // C++
        'java' => 'java',     // Java
        'python' => 'python3' // Python3
    ];

    $languageId = isset($languageMap[$language]) ? $languageMap[$language] : 'python3';

    $allPassed = true; // 标记所有测试用例是否都通过

    // 遍历每个测试用例
    foreach ($testCases as $testCase) {
        // 组织 Jobe 请求数据
        $requestData = [
            'run_spec' => [
                'language_id' => $languageId,
                'sourcefilename' => 'main.' . ($language === 'python' ? 'py' : ($language === 'java' ? 'java' : 'c')),
                'sourcecode' => $answer,
                'input' => empty($testCase['input']) ? null : $testCase['input'],
            ]
        ];

        // 发送请求到 Jobe 服务器
        $ch = curl_init($jobeServer . '/jobe/index.php/restapi/runs');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // 绕过 SSL 证书验证（仅限开发环境）
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            echo '<div class="alert alert-danger">cURL 请求失败: ' . htmlspecialchars($error) . '</div>';
            curl_close($ch);
            exit;
        }

        $responseData = json_decode($response, true);
        curl_close($ch);

        // 检查 Jobe 服务器响应是否包含错误信息
        if (isset($responseData['error'])) {
            echo '<div class="alert alert-danger">Jobe 服务器返回错误: ' . htmlspecialchars($responseData['error']) . '</div>';
            exit;
        }

        // 获取 stdout 输出
        $output = isset($responseData['stdout']) ? $responseData['stdout'] : '';
        $expectedOutput = $testCase['output'];

        // 检查当前测试用例是否通过
        if (trim($output) !== trim($expectedOutput)) {
            $allPassed = false;
            break; // 只要有一个不通过，就不用继续测试了
        }
    }

    // 设置测试状态
    $status = $allPassed ? 'passed' : 'failed';

    // 插入提交记录到 submissions 表
    $stmt = $conn->prepare("INSERT INTO submissions (user_id, question_id, language, code, status, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $userId, $questionId, $language, $answer, $status);
    $stmt->execute();
    $stmt->close();

    // 返回测试结果
    if ($status === 'passed') {
        echo '<div class="alert alert-success">测试通过！</div>';
    } else {
        echo '<div class="alert alert-danger">测试未通过，输出错误。</div>';
    }
} else {
    echo '<div class="alert alert-danger">非法请求。</div>';
}