<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$assignmentId = $_GET['assignmentId'];
$userId = $_GET['userId'];

// Create database connection
$conn = new mysqli('localhost', 'your_username', 'your_password', 'your_database');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch images based on AssignmentId and StudentID
$sql = "SELECT Path FROM assignmentimages WHERE AssignmentId = ? AND StudentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $assignmentId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while($row = $result->fetch_assoc()) {
    $images[] = $row['Path'];
}

echo json_encode($images);

$stmt->close();
$conn->close();
?>
