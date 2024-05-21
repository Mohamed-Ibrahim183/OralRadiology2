<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins (remove or modify this in production for security)
$servername = getenv('DB_SERVER');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

// Create connection to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// SQL to fetch group names
$sql = "SELECT Name FROM groups";
$result = $conn->query($sql);

$groups = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
  }
  echo json_encode($groups); // rg3 el names as JSON
} else {
  echo json_encode([]);
}

$conn->close();
