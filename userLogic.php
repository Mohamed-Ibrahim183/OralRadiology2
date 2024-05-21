<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

// FIRST OF ALL
require_once('./User.class.php');
require_once('./DataBase.class.php');

require_once('./LoginContext.class.php');
require_once('./LoginByID.php');
require_once('./LoginByEmail.php');

$db = new DATABASE();
$pdo = $db->createConnection("oralradiology");
$user = new USER($pdo);
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];
session_start();

// LOGIC
switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST": // insert
        if ($last === "Insert") {
            $tableColumns = ["Password", "Type", "MSAId", "Name", "Email"];
            $lastID = $user->Insert($_POST, $tableColumns, "users", "");
            if (isset($_FILES["personalImage"])) {
                $selectedUser = $user->getMSAId();
                $user->uploadImage($_FILES["personalImage"], $selectedUser, $lastID);
            }
        }
        if ($last === "Login") {
            $context = new LoginContext();
            $identifier = $_POST["identifier"];
            $password = $_POST["password"];

          //  error_log("Identifier: $identifier");
          //  error_log("Password: $password");

            // Determine if identifier is an email or MSA ID
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                error_log("Using Email Strategy");
                $context->setLoginStrategy(new LoginByEmail($pdo));
            } else {
                error_log("Using MSA ID Strategy");
                $context->setLoginStrategy(new LoginByID($pdo));
            }

            $data = $context->executeLogin($identifier, $password);
            if ($data) {
                error_log("Login successful: " . json_encode($data));
                echo json_encode($data);
            } else {
                error_log("Login failed: User not found or invalid credentials");
                echo json_encode("Error in Identifier or Password (User Not Found)");
            }
        }
        if ($last === "UpdateImage") {
            echo $user->uploadImage($_FILES["Profile"], $_POST["MSAId"], $_POST["Id"]);
        }
        die();
    case "GET":
        if ($path[count($path) - 2] === "getUserID") {
            echo json_encode($user->getUser($last, "Id"));
        }
        if ($path[count($path) - 2] === "getUserMSAId") {
            echo json_encode($user->getUser($last, "MSAId"));
        }
        if ($last === "Users") {
            $results = $user->getAll();
            if ($results) {
                echo json_encode($results);
            }
        }
        if ($path[count($path) - 2] === "Users") {
            echo json_encode($user->getSpecificType($last));
        }
        if ($path[count($path) - 2] === "UserAssignments" && is_numeric($last)) {
            // Add logic for user assignments if needed
        }
}
?>
