<?php
session_start(); // Start PHP session at the very top
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'oralradiology';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($input['username'], $input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username or password not provided']);
        exit;
    }

    $username = $input['username'];
    $password = $input['password'];

    $stmt = $conn->prepare("SELECT Id, Type FROM users WHERE username = ? AND password = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare statement']);
        exit;
    }

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['userId'] = $row['Id']; // Store user ID in session
        echo json_encode(['redirect' => true, 'usertype' => $row['Type'], 'userId' => $row['Id']]);
    } else {
        echo json_encode(['error' => 'Invalid username or password']);
    }
    $stmt->close();
}

$conn->close();
