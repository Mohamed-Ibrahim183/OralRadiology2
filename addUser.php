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
function saveImage($id, $pdo)
{
  $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
  $file = $_FILES["personalImage"];
  $fileName = $file["name"];
  $fileSize = $file["size"];
  $fileTmpName = $file["tmp_name"];
  $fileType = $file["type"];
  $fileError = $file["error"];

  $allowedTypes = array("image/jpeg", "image/png", "image/gif");
  if (!in_array($fileType, $allowedTypes)) {
    echo "Error: File is not an image.";
    // exit();
    return;
  }

  // Check file size
  if ($fileSize > $maxFileSize) {
    echo "Error: File size exceeds the maximum limit (5MB).";
    // exit();
    return;
  }

  $targetDir = "./uploads/";
  $targetFile = $targetDir . $fileName;

  if (move_uploaded_file($fileTmpName, $targetFile)) {
    $query = "Update users set personalImage =:target where Id =:selected;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":target", $targetFile);
    $stmt->bindParam(":selected", $id);
    $stmt->execute();
    echo "DONE";
  } else {
    echo "Sorry, there was an error uploading your file.";
  }
}


// print_r($_POST);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $pdo = createConnection();
  $userType = trim($_POST["Type"]);
  $tableColumns = [ "Password","MSAId","Name","Email", "Type"];
  $lastIndex = Insert($pdo, $_POST, $tableColumns, "users", "Password");

  if (isset($_FILES["personalImage"])) {
    saveImage($lastIndex, $pdo);
  }


  $table2 = match ($userType) {
    "Student" => "Insert into students (user) values (:user)",
    "Professor" => "Insert into professors (user) values (:user)",
    "Admin" => "Insert into admins (user) values (:user)",
  };
  $lastIndex = $pdo->lastInsertId();
  if ($lastIndex > 0) {

    $stmt = $pdo->prepare($table2);
    $stmt->bindParam(":user", $lastIndex);
    $stmt->execute();
  }

  $pdo = $stmt = null;
  die();
}
