<?php
function logAction($conn, $code, $action) {
    $stmt = $conn->prepare("INSERT INTO logs (code, action) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $action);
    $stmt->execute();
}
