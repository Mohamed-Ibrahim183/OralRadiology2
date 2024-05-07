<?php
require 'connection.php';

$userId = isset($_POST['userId']) ? intval($_POST['userId']) : null;
$assignmentId = isset($_POST['assignmentId']) ? intval($_POST['assignmentId']) : null;

if (!$userId || !$assignmentId) {
    echo json_encode(['error' => 'Missing user or assignment ID']);
    exit;
}

// Fetch MSAId from the users table
$msaIdQuery = "SELECT MSAId FROM users WHERE Id = ?";
$msaIdStmt = $conn->prepare($msaIdQuery);
if (!$msaIdStmt) {
    echo json_encode(['error' => 'Failed to prepare MSAId statement: ' . $conn->error]);
    exit;
}
$msaIdStmt->bind_param('i', $userId);
$msaIdStmt->execute();
$msaIdStmt->bind_result($msaId);
$msaIdStmt->fetch();
$msaIdStmt->close();

if (!$msaId) {
    echo json_encode(['error' => 'MSAId not found for user']);
    exit;
}

$targetDirectory = $_SERVER['DOCUMENT_ROOT'] . "/Projects/uploads/$msaId/";
$response = [];

foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
    $originalName = basename($_FILES['images']['name'][$key]);
    $newFileName = "assignment{$assignmentId}_user{$userId}_{$originalName}";
    $targetPath = $targetDirectory . $newFileName;

    // Move the uploaded file
    if (move_uploaded_file($tmpName, $targetPath)) {
        // SQL query to insert the path
        $sql = "INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId) VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
            exit;
        }
        $dbPath = "Projects/uploads/$msaId/" . $newFileName; 
        $stmt->bind_param('sii', $dbPath, $userId, $assignmentId);

        // Execute the statement
        if ($stmt->execute()) {
            $response[] = [
                'success' => 'Record inserted successfully',
                'path' => "http://localhost/" . $dbPath
            ];
        } else {
            $response[] = ['error' => 'Execute error: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $response[] = ['error' => 'Failed to upload file: ' . $originalName];
    }
}

echo json_encode($response);
?>
