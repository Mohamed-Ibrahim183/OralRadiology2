<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

// FIRST OF ALL
require_once('./User.class.php');
require_once('./DataBase.class.php');
require_once('./vars.class.php');


$db = new DATABASE();
$pdo = $db->createConnection();
$user = new USER($pdo);


$QueryArr = explode("/", trim(explode('?', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']))[0], '/'));
$Action = $QueryArr[0];


// LOGIC
switch ($_SERVER["REQUEST_METHOD"]) {
	case "POST": // insert
		switch ($Action) {
			case "InsertMSAId":
				$res = $user->insertByMSAId($_POST);
				echo $res ? "INSERTED" : "ERROR";
				break;
			case "ChangeUserProfile":
				$res = $user->saveUserChanges($_POST);
				echo $res ? "UPDATED" : "ERROR";
				break;
			case "Login":
				if (isset($_POST["identifier"]) && isset($_POST["password"]))
					echo json_encode($user->Login($_POST["identifier"], $_POST["password"]));
				else
					echo json_encode(["Error" => "missing values identifier or password"]);
				break;
			case "UpdateImage":
				echo $user->uploadImage($_FILES["Profile"], "../uploads/{$_POST["MSAId"]}", $_POST["Id"]);
				break;
			case "GetTotalUsersType":
				$done = $user->getTotalUsers($_POST["Type"]);
				echo ($done[0]["user_count"]);
				break;
			case "changePassword":
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
			case "UsersTypes":
				echo json_encode($user->getTypes());
				break;
			case "UsersOfType":
				// print_r($_GET);
				echo json_encode($user->getSpecificType($_GET["Type"]));
				break;
			case "Delete":
				$user->deleteUser($_GET["userId"]);
				break;
			case "resetPassword":
				$user->resetPassword($_GET["userId"]);
				break;
		}
		die();
}
