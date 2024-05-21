<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *');

// Database connection parameters
$servername = getenv('DB_SERVER');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  error_log("Connection failed: " . $conn->connect_error);
  exit('Connection failed');
}
echo "Connected successfully";



?>