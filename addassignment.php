<?php

session_start();
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "oralradiology"; 
try {
    if (!isset($_SESSION['userId'])) {
        throw new Exception('Professor ID not found in session.');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("Invalid JSON data received.");
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception('Failed to connect to the database: ' . $conn->connect_error);
    }

    if (!$conn->begin_transaction()) {
        throw new Exception("Failed to start transaction: " . $conn->error);
    }

    $stmt = $conn->prepare("INSERT INTO assignments (Name, ProfessorId, maxLimitImages, Topic) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("siis", $data['requirementName'], $_SESSION['userId'], $data['maxImages'], $data['topicName']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert assignment: ' . $stmt->error);
    }
    $assignmentId = $stmt->insert_id;
    $stmt->close();

    foreach ($data['groups'] as $groupId => $times) {
        $stmt = $conn->prepare("INSERT INTO groupsassignments (`open`, `close`, Assignment, `Group`) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ssii", $times['openTime'], $times['closeTime'], $assignmentId, $groupId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert group assignment: ' . $stmt->error);
        }
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'assignmentId' => $assignmentId]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}

?>
