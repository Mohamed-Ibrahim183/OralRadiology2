<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
// session_start();
require_once("./Helpers.class.php");

class USER
{
  private $Id, $Password, $MSAId, $Name, $Email, $Type, $PersonalImage, $lastIndex;
  private PDO $pdo;
  private Helpers $helpers;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    $this->helpers = new Helpers($pdo);
  }
  public function Insert($postKeys, $tableColumns, $tableName, $hashName)
  {
    $ready = true;
    foreach ($postKeys as $key => $value) {
      if (htmlspecialchars(trim($value)) === "")
        $ready = false;
    }
    if ($ready === true) {
      // $this->Id = $postKeys["Id"];
      $this->Password = $postKeys["Password"];
      $this->MSAId = $postKeys["MSAId"];
      $this->Name = $postKeys["Name"];
      $this->Email = $postKeys["Email"];
      $this->Type = $postKeys["Type"];
      if (isset($postKeys["PersonalImage"])) {
        $this->PersonalImage = $postKeys["PersonalImage"];
      }
    }
    if ($ready === true) {
      // require("./includes.inc.php");
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

      $stmt = $this->pdo->prepare($query);
      $stmt->execute();
      $this->lastIndex = $this->pdo->lastInsertId();
      $this->Id = $this->pdo->lastInsertId();

      return $this->pdo->lastInsertId();
    }
    return -1;
  }

  public function getTotalUsers($usersType)
  {
    $query = "SELECT Type, COUNT(*) AS user_count FROM users WHERE Type=:Selected;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":Selected", $usersType);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return ($result);
  }
  public function getUser($value, $key = "Id")
  {
    $query = "Select * from users Where $key =:selected;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $value);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
      $this->Id = $result["Id"];
      $this->Password = $result["Password"];
      $this->MSAId = $result["MSAId"];
      $this->Name = $result["Name"];
      $this->Email = $result["Email"];
      $this->Type = $result["Type"];
      $this->PersonalImage = $result["PersonalImage"];
      return $result;
    }
  }

  public function uploadImage($file, $targetDir, $id)
  {
    // Max file size: 5MB
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes

    // Extract file information
    $fileName = $file["name"];
    $fileSize = $file["size"];
    $fileTmpName = $file["tmp_name"];
    $fileType = $file["type"];

    // Allowed file types
    $allowedTypes = array("image/jpeg", "image/png", "image/gif");

    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
      return "Error: File is not an image.";
    }

    // Validate file size
    if ($fileSize > $maxFileSize) {
      return "Error: File size exceeds the maximum limit (5MB).";
    }

    // Directory for uploads
    // $targetDir = "../uploads/" . $userID;

    // Create uploads directory if it doesn't exist
    if (!is_dir($targetDir)) {
      if (!mkdir($targetDir, 0777, true)) {
        return "Error: Failed to create directory.";
      }
    }

    // Delete existing file with the name "PersonalImage"
    $existingFiles = glob($targetDir . "/PersonalImage.*");
    foreach ($existingFiles as $existingFile) {
      unlink($existingFile);
    }

    // Generate unique filename
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $targetFile = $targetDir . "/PersonalImage." . $extension;

    // Move uploaded file to target directory
    if (move_uploaded_file($fileTmpName, $targetFile)) {
      // Update database with file path
      $query = "UPDATE users SET personalImage = :target WHERE Id = :selected;";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":target", $targetFile);
      $stmt->bindParam(":selected", $id);
      if ($stmt->execute()) {
        return $targetFile;
      } else {
        return "Error: Database update failed.";
      }
    } else {
      return "Error: Failed to move uploaded file.";
    }
  }

  public function getMSAId()
  {
    return $this->MSAId;
  }
  public function setMSAId($newID)
  {
    require_once("./DataBase.class.php");
    $db = new DATABASE();
    $pdo = $db->createConnection("oralradiology");
    $result = $this->getUser($newID, "MSAId");
    if ($result) {
      // done
    }
  }
  public function Login($MSAId, $password)
  {
    $query = "SELECT * FROM users WHERE MSAId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $MSAId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $logged = false;

    if ($user && $password === $user["Password"]) {
      unset($user["Password"]);
      return $user;
    } else {
      echo "not Logged In";
      return false;
    }
  }

  public function getIMage($id)
  {
    $result = $this->getUser($id, "Id");
    if ($this->PersonalImage)
      return $this->PersonalImage;
  }
  public function getAll()
  {
    $query = "Select * from users;";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results) {
      foreach ($results as $key => $value) {
        unset($value["Password"]);
      }
    }
    return $results;
  }

  public function getSpecificType($type)
  {
    require_once("./Group.class.php");
    $group = new GROUP($this->pdo);
    $query = "Select * from users Where Type=:selected;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $type);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$result)
      return null;
    foreach ($result as $key => $value) {
      unset($result[$key]["Password"]);
      $userGroup = $group->getUserGroup($value["Id"]);
      if ($userGroup != -1 && $userGroup) {
        $result[$key]["Group"] = $userGroup["Name"];
      }
    }
    return $result;
  }
  public function deleteUser($userId)
  {
    $query = "Delete from users Where Id=:id;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":id", $userId);
    $stmt->execute();
  }

  public function insertByMSAId($userData)
  {
    $this->helpers->prepareAndBind("INSERT into users (MSAId, Type, Password) Values", [
      "MSAId" => $userData["MSAId"],
      "Type" => $userData["Type"],
      "Password" => "pass",
    ], true);
    return true;
  }
  public function saveUserChanges($changes)
  {
    $stmt = $this->pdo->prepare("UPDATE users set Email=:email, Name=:name where MSAId=:msa;");
    $this->helpers->bindParams([
      "email" => $changes["Email"],
      "name" => $changes["username"],
      "msa" => $changes["MSAId"],
    ], $stmt, true);
    return true;
  }
}
