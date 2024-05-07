<?php
require 'connection.php';
require 'Assignment.class.php';

$assignment = new Assignment($conn);  

$userId = isset($_POST['userId']) ? intval($_POST['userId']) : null;
$assignmentId = isset($_POST['assignmentId']) ? intval($_POST['assignmentId']) : null;

if (!$userId || !$assignmentId) {
    echo json_encode(['error' => 'Missing user or assignment ID']);
    exit;
}

$assignmentInfo = $assignment->fetchAssignment($assignmentId);
$maxImages = $assignmentInfo['maxLimitImages'] ?? 0;

// Process file uploads
$files = $_FILES['images'];
$response = $assignment->uploadImages($files, $assignmentId, $userId, $maxImages);
echo json_encode($response);
?>
