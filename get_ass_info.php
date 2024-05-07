<?php

require'connection.php';

$logFile = "php_access_log.txt";
file_put_contents($logFile, "Access at " . date("Y-m-d H:i:s") . "\n", FILE_APPEND);


// Retrieve assignment ID from GET request
$assignmentId = isset($_GET['assignmentId']) ? intval($_GET['assignmentId']) : null;

if ($assignmentId) {
    $stmt = $conn->prepare("SELECT Name, Topic, maxLimitImages FROM assignments WHERE Id = ?");
    $stmt->bind_param("i", $assignmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignmentData = $result->fetch_assoc();

    if ($assignmentData) {
        echo json_encode($assignmentData);
        file_put_contents($logFile, "Data fetched for assignment ID $assignmentId: " . json_encode($assignmentData) . "\n", FILE_APPEND);
    } else {
        echo json_encode(["error" => "No data found for assignment ID $assignmentId"]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Assignment ID is not provided"]);
}

$conn->close(); 
?>
