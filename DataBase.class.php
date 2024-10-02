<?php
require_once("./vars.class.php");
class DATABASE
{
  public PDO $PDO;
  public $globals;
  public function __construct()
  {
    $this->globals = new vars();
  }
  function createConnection()
  {
    $dsn = $this->globals->dsn;
    $dbUserName = $this->globals->dbUserName;
    $dbPassword = $this->globals->dbPassword;

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
    $serverName = "localhost";
    $username = "root";
    $password = "";
    $dbname = "oralradiology";

    // Create connection
    $conn = new mysqli($serverName, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
  }
}
