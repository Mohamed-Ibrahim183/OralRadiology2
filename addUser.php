<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

function createConnection()
{
  $dsn = "mysql:host=localhost;dbname=oralradiology";
  $dbUserName = "root";
  $dbPassword = "";

  try {
    $pdo = new PDO($dsn, $dbUserName, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
  } catch (PDOException $e) {
    echo "Connection Failed: " . $e->getMessage() . "<br>";
    return null;
  }
}

function getBoundParameters($stmt)
{
  $boundParams = [];

  // Get bound parameters and their values
  foreach ($stmt->debugDumpParams() as $param) {
    $boundParams[$param['paramName']] = $param['paramValue'];
  }

  return $boundParams;
}

function Insert($pdo, $postKeys, $tableColumns, $tableName, $hashName)
{
  $ready = true;
  foreach ($postKeys as $key => $value) {
    if (htmlspecialchars(trim($value)) === "")
      $ready = false;
  }

  if ($ready === true) {
    require("./includes.inc.php");
    if ($hashName !== "")
      $postKeys[$hashName] = password_hash($postKeys[$hashName], PASSWORD_BCRYPT, ['cost' => 12]);

    $query = "INSERT INTO $tableName (";
    $query .= implode(", ", $tableColumns);
    $query .= ") VALUES (";
    foreach ($tableColumns as $key => $value) {
      $variable = htmlspecialchars(trim($postKeys[$value]));
      $query .= "'$variable', ";
    }
    $query = rtrim($query, ", "); // Remove trailing comma
    $query .= ")";

    $stmt = $pdo->prepare($query);
    // Execute the statement
    $stmt->execute();
    return $pdo->lastInsertId();
  }
  return -1;
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $pdo = createConnection();
  $userType = trim($_POST["Type"]);
  $tableColumns = ["Username", "Password", "Type", "MSAId", "Name", "Email"];
  $lastIndex = Insert($pdo, $_POST, $tableColumns, "users", "Password");

  $table2 = match ($userType) {
    "Student" => "Insert into students (user) values (:user)",
    "Professor" => "Insert into professors (user) values (:user)",
    "Admin" => "Insert into admins (user) values (:user)",
  };
  // $lastIndex = $pdo->lastInsertId();
  if ($lastIndex > 0) {

    $stmt = $pdo->prepare($table2);
    $stmt->bindParam(":user", $lastIndex);
    $stmt->execute();
  }

  $pdo = $stmt = null;
  die();
}
