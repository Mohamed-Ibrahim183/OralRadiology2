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
}
