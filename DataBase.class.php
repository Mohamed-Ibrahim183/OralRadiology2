<?php
class DATABASE
{
  public $PDO;
  function createConnection($name = "oralradiology")
  {
    $dsn = "mysql:host=localhost;dbname=$name";
    $dbUserName = "root";
    $dbPassword = "";

    try {
      $pdo = new PDO($dsn, $dbUserName, $dbPassword);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->PDO = $pdo;
      return $pdo;
    } catch (PDOException $e) {
      echo "Connection Failed: " . $e->getMessage() . "<br>";
      return null;
    }
  }
  public function Connection2()
  {

    // Database connection parameters
    $servername = getenv('DB_SERVER');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    $dbname = getenv('DB_NAME');

    // Create connection
    $conn = new mysqli($serverName, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
  }
}
