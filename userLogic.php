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


$QueryArr = explode("/", trim(explode('?', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']))[0], '/'));
$Action = $QueryArr[0];


// LOGIC
switch ($_SERVER["REQUEST_METHOD"]) {
	case "POST": // insert
		switch ($Action) {
			case "Insert":
				$tableColumns = ["Password", "Type", "MSAId", "Name", "Email"];
				$lastID = $user->Insert($_POST, $tableColumns, "users", "");
				if (isset($_FILES["personalImage"])) {
					$selectedUser = $user->getMSAId();
					$user->uploadImage($_FILES["personalImage"], "../uploads/{$selectedUser}", $lastID);
				}
				break;
			case "InsertMSAId":
				$res = $user->insertByMSAId($_POST);
				echo $res ? "INSERTED" : "ERROR";
				break;
			case "ChangeUserProfile":
				$res = $user->saveUserChanges($_POST);
				echo $res ? "UPDATED" : "ERROR";
				break;
			case "Login":
				$context = new LoginContext();
				$identifier = $_POST["identifier"];
				$password = $_POST["password"];
				error_log("Identifier: $identifier");
				error_log("Password: $password");
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
				break;
			case "UpdateImage":
				echo $user->uploadImage($_FILES["Profile"], "../uploads/{$_POST["MSAId"]}", $_POST["Id"]);
				break;
			case "GetTotalUsersType":
				$done = $user->getTotalUsers($_POST["Type"]);
				echo ($done[0]["user_count"]);
				break;
			case "changePassword":
				// print_r($_POST);
				$user->changePassword($_POST);
				break;
		}
		die();
	case "GET":
		switch ($Action) {
			case "getUserID":
				echo json_encode($user->getUser($last, "Id"));
				break;
			case "getUserMSAId":
				echo json_encode($user->getUser($last, "MSAId"));
				break;
			case "Users":
				echo json_encode($user->getAll());
				break;
			case "UsersOfType":
				echo json_encode($user->getSpecificType($_GET["Type"]));
				break;
			case "Delete":
				$user->deleteUser($_GET["userId"]);
				break;
		}
		die();
}
