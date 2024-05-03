<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost'; 
$username = 'root'; 
$password = ''; 
$database = 'oralradiology';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

$userId = $_GET['userId'] ?? null; 
if ($userId) {
    $stmt = $conn->prepare("SELECT Name, PersonalImage FROM users WHERE Id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $imageUrl = 'http://localhost/Projects/Oral Radiology/' . $row['PersonalImage'];
        echo json_encode(['name' => $row['Name'], 'personalImage' => $imageUrl]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    
    

    $stmt->close();
} else {
    echo json_encode(['error' => 'No user ID provided']);
}

$conn->close();
?>
