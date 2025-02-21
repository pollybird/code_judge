<?php
function get_site_config($conn, $configName) {
    $stmt = $conn->prepare("SELECT value FROM site_config WHERE name = ?");
    $stmt->bind_param("s", $configName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['value'];
    }
    $stmt->close();
    return null;
}