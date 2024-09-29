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
  public function changePassword($data)
  {
    $query = "UPDATE users SET Password=:pass WHERE Id=:id";
    $stmt = $this->pdo->prepare($query);
    $this->helpers->bindParams(([
      // "pass" => $data["password"],
      "pass" => password_hash($data["password"], PASSWORD_BCRYPT, ['cost' => 12]),
      "id" => $data["Id"],
    ]), $stmt, true); // Bind parameters and execute query
    return true;
  }
  public function resetPassword($userId)
  {
    $stmt = $this->pdo->prepare("UPDATE users SET Password=:pass WHERE Id=:selected");
    $stmt->execute([":selected" => $userId, ":pass" => password_hash("pass", PASSWORD_BCRYPT, ['cost' => 12])]);
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
      // 2. get the id of the group
      $stmt = $this->pdo->prepare("SELECT GroupId from usersingroups WHERE userId=:user");
      $stmt->execute(["user" => $result["Id"]]);
      $groupId = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($groupId["GroupId"]) {
        $stmt = $this->pdo->prepare("SELECT Name FROM groups WHERE Id=:groupId");
        $stmt->execute(["groupId" => $groupId["GroupId"]]);
        $groupName = $stmt->fetch(PDO::FETCH_ASSOC);
        $result["Group"] = $groupName["Name"];
      }


      unset($result["Password"]);
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
    if (!in_array($fileType, $allowedTypes))
      return "Error: File is not an image.";


    // Validate file size
    if ($fileSize > $maxFileSize)
      return "Error: File size exceeds the maximum limit (5MB).";


    // Directory for uploads
    // $targetDir = "../uploads/" . $userID;

    // Create uploads directory if it doesn't exist
    if (!is_dir($targetDir)) {
      if (!mkdir($targetDir, 0777, true))
        return "Error: Failed to create directory.";
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
      // $stmt->execute();
      if ($stmt->execute())
        return $targetFile;
      else
        return "Error: Database update failed.";
    } else
      return "Error: Failed to move uploaded file.";
  }

  public function Login($identifier, $password)
  {
    $query = "SELECT * from users where ";
    $query .= filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "Email=:identifier" : "MSAId=:identifier";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":identifier", $identifier);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
      if (password_verify($password, $user["Password"]) || $password === $user["Password"]) {
        unset($user["Password"]);

        // 2. get the id of the group
        $stmt = $this->pdo->prepare("SELECT GroupId from usersingroups WHERE userId=:user");
        $stmt->execute(["user" => $user["Id"]]);
        $groupId = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($groupId["GroupId"]) && $groupId["GroupId"]) {
          $stmt = $this->pdo->prepare("SELECT Name FROM groups WHERE Id=:groupId");
          $stmt->execute(["groupId" => $groupId["GroupId"]]);
          $groupName = $stmt->fetch(PDO::FETCH_ASSOC);
          $user["Group"] = $groupName["Name"];
        }
      } else
        return ["Error" => "Password for $identifier is incorrect"];
    } else
      return ["Error" => "User Not Found [Contact With Admin]"];
    return $user;
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
