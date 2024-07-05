<?php
// require 'connection.php';
require 'Assignment.class.php';
require 'User.class.php';
require_once('./DataBase.class.php');

$conn = new DATABASE();
$conn = $conn->Connection2();
$assignment = new Assignment($conn);

$userId = isset($_POST['userId']) ? intval($_POST['userId']) : null;
$assignmentId = isset($_POST['assignmentId']) ? intval($_POST['assignmentId']) : null;

if (!$userId || !$assignmentId) {
    echo json_encode(['error' => 'Missing user or assignment ID']);
    exit;
}

$assignmentInfo = $assignment->fetchAssignment($assignmentId);
$maxImages = $assignmentInfo['maxLimitImages'] ?? 0;
$Name = $assignmentInfo['Name'] ?? 0;

$db = new DATABASE();
$pdo = $db->createConnection("oralradiology");
$user = new USER($pdo);

$monem = $user->getUser($userId, 'Id');
$MSAId = $monem['MSAId'] ?? 0;


// Process file uploads
$files = $_FILES['images'];
$response = $assignment->uploadImages($files, $assignmentId, $userId, $maxImages, $Name, $MSAId);
echo json_encode($response);
